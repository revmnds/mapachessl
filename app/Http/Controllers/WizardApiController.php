<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateCertificate;
use App\Models\CertificateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class WizardApiController extends Controller
{
    private const COOKIE_NAME = 'ssl_wizard_token';

    /**
     * Resolve session token from URL query param (?s=) or cookie
     */
    private function getToken(): ?string
    {
        return request()->query('s') ?: request()->cookie(self::COOKIE_NAME);
    }

    private const COOKIE_MINUTES = 60 * 24 * 7; // 7 days

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

        $response = response()->view('wizard.app', [
            'wizardSession' => $sessionData,
        ]);

        // Set cookie if token came from URL param (mirrors status() behavior)
        if (request()->query('s') && $token && $sessionData) {
            $response->withCookie($this->makeSecureCookie($token));
        }

        return $response;
    }

    public function status(): JsonResponse
    {
        $token = $this->getToken();
        $request = $token ? CertificateRequest::findByTokenAny($token) : null;

        // Solo mostrar resume si hay progreso real (al menos dominio guardado)
        if (!$request || !$request->domain) {
            return response()->json(['has_session' => false]);
        }

        $response = response()->json([
            'has_session' => true,
            'data' => $this->formatRequestData($request)
        ]);

        // Si el token viene de URL param, setear cookie para requests futuros
        if (request()->query('s') && $token) {
            $response->withCookie($this->makeSecureCookie($token));
        }

        return $response;
    }

    public function discard(): JsonResponse
    {
        $token = $this->getToken();
        if ($token) {
            $existing = CertificateRequest::findByTokenAny($token);
            if ($existing) {
                $existing->delete();
            }
        }

        return response()->json(['success' => true])
            ->withCookie($this->makeDeleteCookie());
    }

    public function start(): JsonResponse
    {
        // Delete any existing session (stops running jobs via cancellation detection)
        // Only read from cookie — never delete based on URL param (prevents shared URL destruction)
        $cookieToken = request()->cookie(self::COOKIE_NAME);
        if ($cookieToken) {
            CertificateRequest::where('session_token', $cookieToken)->delete();
        }

        $certRequest = CertificateRequest::createNew();

        return response()->json([
            'success' => true,
            'data' => ['session_token' => $certRequest->session_token],
        ])->withCookie($this->makeSecureCookie($certRequest->session_token));
    }

    public function startFresh(): JsonResponse
    {
        // Delete any existing session (stops running jobs via cancellation detection)
        // Only read from cookie — never delete based on URL param (prevents shared URL destruction)
        $cookieToken = request()->cookie(self::COOKIE_NAME);
        if ($cookieToken) {
            CertificateRequest::where('session_token', $cookieToken)->delete();
        }

        $certRequest = CertificateRequest::createNew();

        return response()->json([
            'success' => true,
            'data' => ['session_token' => $certRequest->session_token],
        ])->withCookie($this->makeSecureCookie($certRequest->session_token));
    }

    public function saveStep(Request $request, int $step): JsonResponse
    {
        $certRequest = $this->getCertificateRequest();
        if (!$certRequest) {
            return response()->json(['success' => false, 'error' => 'No session'], 401);
        }

        $rules = match ($step) {
            1 => [
                'domain' => 'required|string|max:255|regex:/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/',
                'is_wildcard' => 'boolean',
            ],
            2 => ['email' => 'required|email|max:255'],
            3 => ['challenge_type' => 'required|in:http,dns'],
            default => [],
        };

        $messages = [
            'domain.required' => __('messages.validation.domain_required'),
            'domain.regex' => __('validation.custom.domain.regex'),
            'domain.max' => __('validation.max.string', ['attribute' => __('validation.attributes.domain'), 'max' => 255]),
            'email.required' => __('messages.validation.email_required'),
            'email.email' => __('messages.validation.email_invalid'),
            'email.max' => __('validation.max.string', ['attribute' => __('validation.attributes.email'), 'max' => 255]),
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
        if ($step === 3 && $certRequest->is_wildcard && ($validated['challenge_type'] ?? '') === 'http') {
            return response()->json([
                'success' => false,
                'errors' => ['challenge_type' => __('messages.validation.wildcard_requires_dns', [], 'Wildcard certificates require DNS verification.')],
            ], 422);
        }

        $certRequest->update([
            ...$validated,
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

        // Atomic check-and-lock inside a transaction
        $certRequest = DB::transaction(function () use ($token) {
            $certRequest = CertificateRequest::where('session_token', $token)
                ->whereIn('status', ['in_progress', 'failed'])
                ->lockForUpdate()
                ->first();

            if (!$certRequest) {
                return null;
            }

            if ($certRequest->isGenerating()) {
                return 'already_generating';
            }

            if ($certRequest->status === 'failed') {
                $certRequest->resetForRetry();
            }

            $certRequest->lockGeneration();
            return $certRequest;
        });

        if ($certRequest === null) {
            return response()->json(['success' => false, 'error' => 'No session'], 401);
        }

        if ($certRequest === 'already_generating') {
            return response()->json([
                'success' => false,
                'errors' => ['verification' => __('messages.errors.generation_in_progress')],
            ], 409);
        }

        // Validate all required fields are present before dispatching
        if (!$certRequest->domain || !$certRequest->email || !$certRequest->challenge_type) {
            $certRequest->unlockGeneration();
            return response()->json(['success' => false, 'error' => 'Incomplete request'], 400);
        }

        GenerateCertificate::dispatch($certRequest->id);

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

        // Detect stale generation (queue worker crashed)
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

        $zipFileName = storage_path("app/temp/{$certRequest->domain}-ssl.zip");
        $tempDir = dirname($zipFileName);

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0700, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
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
            'session_token' => $request->session_token,
            'domain' => $request->domain,
            'is_wildcard' => $request->is_wildcard ?? false,
            'display_domain' => $request->getDisplayDomain(),
            'email' => $request->email,
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
