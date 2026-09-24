<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateCertificate;
use App\Models\CertificateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use ZipArchive;

class WizardApiController extends Controller
{
    private const COOKIE_NAME = 'ssl_wizard_token';
    private const COOKIE_MINUTES = 60 * 24 * 7; // 7 days

    private const GENERATE_LIMIT_PER_HOUR = 5;

    /**
     * Session token lives only in an httpOnly cookie (never in the URL)
     */
    private function getToken(): ?string
    {
        return request()->cookie(self::COOKIE_NAME);
    }

    /**
     * Create a secure cookie for the session token
     */
    private function makeSecureCookie(string $value, int $minutes = self::COOKIE_MINUTES): \Symfony\Component\HttpFoundation\Cookie
    {
        return cookie(
            self::COOKIE_NAME,
            $value,
            $minutes,
            '/',           // path
            null,          // domain
            request()->secure(), // secure only when on HTTPS
            true,          // httpOnly
            false,         // raw
            'Lax'          // sameSite
        );
    }

    /**
     * Create a cookie to delete the session
     */
    private function makeDeleteCookie(): \Symfony\Component\HttpFoundation\Cookie
    {
        return cookie(self::COOKIE_NAME, '', -1, '/', null, request()->secure(), true, false, 'Lax');
    }

    public function index()
    {
        $token = $this->getToken();
        $request = $token ? CertificateRequest::findByTokenAny($token) : null;

        $sessionData = null;
        if ($request && $request->domain) {
            $full = $this->formatRequestData($request);
            // Strip PEM fields — don't embed private keys in HTML source
            unset($full['certificate_pem'], $full['private_key_pem'], $full['chain_pem'], $full['fullchain_pem']);
            $sessionData = ['has_session' => true, 'data' => $full];
        }

        return response()->view('wizard.app', [
            'wizardSession' => $sessionData,
        ]);
    }

    /**
     * Fresh CSRF token, so the frontend can recover after the Laravel session expired
     */
    public function csrf(): JsonResponse
    {
        return response()->json(['token' => csrf_token()]);
    }

    public function status(): JsonResponse
    {
        $token = $this->getToken();
        $request = $token ? CertificateRequest::findByTokenAny($token) : null;

        // Solo mostrar resume si hay progreso real (al menos dominio guardado)
        if (!$request || !$request->domain) {
            return response()->json(['has_session' => false]);
        }

        return response()->json([
            'has_session' => true,
            'data' => $this->formatRequestData($request)
        ]);
    }

    public function discard(): JsonResponse
    {
        $token = $this->getToken();
        if ($token) {
            CertificateRequest::where('session_token', $token)->delete();
        }

        return response()->json(['success' => true])
            ->withCookie($this->makeDeleteCookie());
    }

    public function start(): JsonResponse
    {
        // Delete any existing session (running jobs detect it and stop)
        $token = $this->getToken();
        if ($token) {
            CertificateRequest::where('session_token', $token)->delete();
        }

        $certRequest = CertificateRequest::createNew();

        return response()->json(['success' => true])
            ->withCookie($this->makeSecureCookie($certRequest->session_token));
    }

    public function saveStep(Request $request, int $step): JsonResponse
    {
        $certRequest = $this->getCertificateRequest(allowFailed: true);
        if (!$certRequest) {
            return response()->json(['success' => false, 'error' => 'No session'], 401);
        }

        // Editing while a job runs would issue a certificate for data that no longer matches the record
        if ($certRequest->status === 'in_progress' && $certRequest->isGenerating()) {
            return response()->json([
                'success' => false,
                'errors' => ['server' => __('messages.errors.generation_in_progress')],
            ], 409);
        }

        $rules = match ($step) {
            1 => [
                'domain' => 'required|string|max:255|regex:/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/',
                'is_wildcard' => 'boolean',
            ],
            2 => ['challenge_type' => 'required|in:http,dns'],
        };

        $messages = [
            'domain.required' => __('messages.validation.domain_required'),
            'domain.regex' => __('validation.custom.domain.regex'),
            'domain.max' => __('validation.max.string', ['attribute' => __('validation.attributes.domain'), 'max' => 255]),
            'challenge_type.required' => __('messages.validation.challenge_type_required'),
            'challenge_type.in' => __('messages.validation.challenge_type_invalid'),
        ];

        $validator = validator($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->toArray()
            ], 422);
        }

        $validated = $validator->validated();

        // Si es wildcard, forzar DNS como método de verificación
        if ($step === 1 && ($validated['is_wildcard'] ?? false)) {
            $validated['challenge_type'] = 'dns';
        }

        // Wildcard certificates always require DNS challenge
        if ($step === 2 && $certRequest->is_wildcard && ($validated['challenge_type'] ?? '') === 'http') {
            return response()->json([
                'success' => false,
                'errors' => ['challenge_type' => __('messages.validation.wildcard_requires_dns', [], 'Wildcard certificates require DNS verification.')],
            ], 422);
        }

        // Going back to fix something after a failure reopens the request
        $reopen = $certRequest->status === 'failed'
            ? ['status' => 'in_progress', 'error_message' => null, 'challenge_token' => null, 'challenge_filename' => null]
            : [];

        $certRequest->update([
            ...$validated,
            ...$reopen,
            'current_step' => $step + 1
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatRequestData($certRequest)
        ]);
    }

    public function generate(): JsonResponse
    {
        $token = $this->getToken();
        if (!$token) {
            return response()->json(['success' => false, 'error' => 'No session'], 401);
        }

        $rateKey = 'generate:' . request()->ip();
        if (RateLimiter::tooManyAttempts($rateKey, self::GENERATE_LIMIT_PER_HOUR)) {
            return response()->json([
                'success' => false,
                'errors' => ['rate_limit' => __('messages.rate_limit.generate')],
            ], 429);
        }

        // Atomic check-and-lock inside a transaction
        $result = DB::transaction(function () use ($token) {
            $certRequest = CertificateRequest::where('session_token', $token)
                ->whereIn('status', ['in_progress', 'failed'])
                ->lockForUpdate()
                ->first();

            if (!$certRequest) {
                return null;
            }

            if ($certRequest->status === 'in_progress' && $certRequest->isGenerating()) {
                return 'already_generating';
            }

            if (!$certRequest->domain || !$certRequest->challenge_type) {
                return 'incomplete';
            }

            // Serialize the capacity check across sessions (row locks only cover this request);
            // released automatically at commit
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('SELECT pg_advisory_xact_lock(?)', [crc32('acme-generation-capacity')]);
            }

            if (CertificateRequest::activeGenerationsCount() >= (int) config('services.acme.max_concurrent')) {
                return 'busy';
            }

            if ($certRequest->status === 'failed') {
                $certRequest->resetForRetry();
            }

            $attempt = $certRequest->lockGeneration();
            return [$certRequest, $attempt];
        });

        if ($result === null) {
            return response()->json(['success' => false, 'error' => 'No session'], 401);
        }

        if ($result === 'already_generating') {
            return response()->json([
                'success' => false,
                'errors' => ['verification' => __('messages.errors.generation_in_progress')],
            ], 409);
        }

        if ($result === 'incomplete') {
            return response()->json(['success' => false, 'error' => 'Incomplete request'], 400);
        }

        if ($result === 'busy') {
            return response()->json([
                'success' => false,
                'errors' => ['server' => __('messages.errors.server_busy')],
            ], 503);
        }

        [$certRequest, $attempt] = $result;

        RateLimiter::hit($rateKey, 3600);
        GenerateCertificate::dispatch($certRequest->id, $attempt);

        return response()->json([
            'success' => true,
            'data' => $this->formatRequestData($certRequest->refresh()),
        ]);
    }

    /**
     * Poll endpoint for frontend to check current challenge tokens
     * Frontend can call this while generate() is running to get the tokens
     */
    public function pollTokens(): JsonResponse
    {
        $certRequest = $this->getCertificateRequest(allowCompleted: true);
        if (!$certRequest) {
            return response()->json(['success' => false, 'error' => 'No session'], 401);
        }

        // Detect stale generation (worker crashed, killed by timeout, or job never picked up)
        if ($certRequest->status === 'in_progress'
            && $certRequest->generation_started_at
            && !$certRequest->isGenerating()
        ) {
            $certRequest->markAsFailed(__('messages.errors.generation_stale'));
            $certRequest->refresh();
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatRequestData($certRequest)
        ]);
    }

    public function download()
    {
        $token = $this->getToken();
        $certRequest = $token ? CertificateRequest::findByTokenAny($token) : null;

        if (!$certRequest || !$certRequest->hasCertificate()) {
            return redirect('/');
        }

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0700, true);
        }

        // Unique file per request: concurrent downloads must not overwrite or delete each other
        $zipFileName = tempnam($tempDir, 'ssl');

        $zip = new ZipArchive();
        if ($zip->open($zipFileName, ZipArchive::OVERWRITE) !== true) {
            @unlink($zipFileName);
            return response()->json(['error' => __('messages.errors.zip_error')], 500);
        }

        $zip->addFromString('certificate.pem', $certRequest->certificate_pem);
        $zip->addFromString('private_key.pem', $certRequest->private_key_pem);

        if ($certRequest->chain_pem) {
            $zip->addFromString('chain.pem', $certRequest->chain_pem);
        }

        $readme = $this->generateReadme($certRequest);
        $zip->addFromString('README.txt', $readme);

        $zip->close();

        return response()->download($zipFileName, "{$certRequest->domain}-ssl.zip")->deleteFileAfterSend(true);
    }

    private function formatRequestData(CertificateRequest $request): array
    {
        $data = [
            'domain' => $request->domain,
            'is_wildcard' => $request->is_wildcard ?? false,
            'display_domain' => $request->getDisplayDomain(),
            'challenge_type' => $request->challenge_type ?? 'http',
            'challenge_token' => $request->challenge_token,
            'challenge_filename' => $request->challenge_filename,
            'current_step' => $request->current_step,
            'status' => $request->status,
            'is_generating' => $request->isGenerating(),
            'error_message' => $request->error_message,
            'expires_at' => $request->expires_at?->format('d/m/Y'),
        ];

        // Include certificate data when completed (for copy-to-clipboard feature)
        if ($request->status === 'completed' && $request->certificate_pem) {
            $data['certificate_pem'] = $request->certificate_pem;
            $data['private_key_pem'] = $request->private_key_pem;
            $data['chain_pem'] = $request->chain_pem;
            // Fullchain = certificate + chain
            $data['fullchain_pem'] = $request->chain_pem
                ? $request->certificate_pem . "\n" . $request->chain_pem
                : $request->certificate_pem;
        }

        return $data;
    }

    private function generateReadme(CertificateRequest $certRequest): string
    {
        $domain = $certRequest->getDisplayDomain();
        $expires = $certRequest->expires_at?->format('d/m/Y') ?? __('readme.unknown_date');
        $path = __('readme.path_hint');

        return __('readme.separator') . "\n" .
            '  ' . __('readme.header', ['domain' => $domain]) . "\n" .
            '  ' . __('readme.generated_by') . "\n" .
            __('readme.separator') . "\n\n" .
            __('readme.files_heading') . "\n" .
            "- certificate.pem   : " . __('readme.cert_desc') . "\n" .
            "- private_key.pem   : " . __('readme.key_desc') . "\n" .
            "- chain.pem         : " . __('readme.chain_desc') . "\n\n" .
            __('readme.info_heading') . "\n" .
            "- " . __('readme.domain_label', ['domain' => $domain]) . "\n" .
            "- " . __('readme.expires_label', ['expires' => $expires]) . "\n" .
            "- " . __('readme.issued_by') . "\n\n" .
            "---------------------------------------------------\n" .
            __('readme.nginx_heading') . "\n" .
            "---------------------------------------------------\n" .
            "server {\n" .
            "    listen 443 ssl http2;\n" .
            "    server_name {$domain};\n\n" .
            "    ssl_certificate     {$path}certificate.pem;\n" .
            "    ssl_certificate_key {$path}private_key.pem;\n\n" .
            "    ssl_protocols TLSv1.2 TLSv1.3;\n" .
            "    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;\n" .
            "    ssl_prefer_server_ciphers off;\n" .
            "}\n\n" .
            "---------------------------------------------------\n" .
            __('readme.apache_heading') . "\n" .
            "---------------------------------------------------\n" .
            "<VirtualHost *:443>\n" .
            "    ServerName {$domain}\n\n" .
            "    SSLEngine on\n" .
            "    SSLCertificateFile      {$path}certificate.pem\n" .
            "    SSLCertificateKeyFile   {$path}private_key.pem\n" .
            "    SSLCertificateChainFile {$path}chain.pem\n" .
            "</VirtualHost>\n\n" .
            "---------------------------------------------------\n" .
            __('readme.important_heading') . "\n" .
            "---------------------------------------------------\n" .
            "1. " . __('readme.important_1') . "\n" .
            "2. " . __('readme.important_2', ['expires' => $expires]) . "\n" .
            "3. " . __('readme.important_3') . "\n\n" .
            __('readme.thanks');
    }

    private function getCertificateRequest(bool $allowFailed = false, bool $allowCompleted = false): ?CertificateRequest
    {
        $token = $this->getToken();
        if (!$token) {
            return null;
        }

        if ($allowCompleted) {
            return CertificateRequest::findByTokenAny($token);
        }

        if ($allowFailed) {
            return CertificateRequest::where('session_token', $token)
                ->whereIn('status', ['in_progress', 'failed'])
                ->first();
        }

        return CertificateRequest::findByToken($token);
    }
}
