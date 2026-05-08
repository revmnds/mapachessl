<?php

namespace App\Services;

use App\Models\CertificateRequest;
use Illuminate\Support\Facades\Log;
use skoerfgen\ACMECert\ACMECert;

class AcmeService
{
    private ACMECert $client;
    private string $accountKeyPath;
    private bool $staging;

    // Polling configuration
    private const MAX_WAIT_TIME = 1800; // 30 minutes max wait
    private const POLL_INTERVAL = 5;    // Check every 5 seconds

    public function __construct()
    {
        $this->staging = config('services.acme.staging', true);
        $this->accountKeyPath = storage_path('app/acme/account_key.pem');

        // Ensure directory exists
        $dir = dirname($this->accountKeyPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Initialize client (true = live, false = staging)
        $this->client = new ACMECert(!$this->staging);
    }

    /**
     * Ensure we have an account key and it's registered with Let's Encrypt
     */
    private function ensureAccount(): void
    {
        // Generate account key if it doesn't exist
        if (!file_exists($this->accountKeyPath)) {
            $accountKey = $this->client->generateRSAKey(4096);
            file_put_contents($this->accountKeyPath, $accountKey);
        }

        // Load the account key
        $this->client->loadAccountKey('file://' . $this->accountKeyPath);

        // Register account (idempotent - returns existing if already registered)
        try {
            $this->client->register(true);
        } catch (\Exception $e) {
            // Already registered is fine
            if (strpos($e->getMessage(), 'already') === false) {
                throw $e;
            }
        }
    }

    /**
     * Generate certificate with polling - waits for DNS/HTTP to be ready
     *
     * This method:
     * 1. Creates an ACME order and gets challenge tokens
     * 2. Saves tokens to DB so user can see them
     * 3. Polls (with sleep) until DNS/HTTP is verified
     * 4. Completes validation and generates certificate
     *
     * @param CertificateRequest $request The certificate request (will be updated with tokens)
     * @return array Result with success/error and certificate data
     */
    public function generateCertificate(CertificateRequest $request, bool $forceNewAuth = false): array
    {
        try {
            $this->ensureAccount();

            $domains = $request->getAllDomains();
            $challengeType = $request->challenge_type === 'dns' ? 'dns-01' : 'http-01';
            $domainKey = $this->client->generateRSAKey(2048);

            // Build domain config
            $domainConfig = [];
            foreach ($domains as $domain) {
                $domainConfig[$domain] = ['challenge' => $challengeType];
            }

            Log::info('Starting certificate generation with polling', [
                'domain' => $request->domain,
                'challenge_type' => $challengeType,
                'is_wildcard' => $request->is_wildcard,
            ]);

            // Track collected challenges for all domains
            $collectedChallenges = [];
            $service = $this;
            $callbackCalled = false;
            $callbackCount = 0;

            // The library calls ALL callbacks first (per group), then validates.
            // We save tokens to DB immediately so the frontend can show them ALL at once.
            // Only the LAST callback blocks (polling) to wait for the user to configure DNS/HTTP.
            //
            // For wildcard: 2 callbacks (domain + *.domain) in same group.
            // Wildcard always uses forceNewAuth to guarantee both callbacks fire.
            // For non-wildcard: 1 callback (auth reuse may skip it entirely if already valid).
            $groupSize = $request->is_wildcard ? 2 : 1;

            $callback = function ($opts) use ($request, $service, &$collectedChallenges, &$callbackCalled, &$callbackCount, $groupSize) {
                $callbackCalled = true;
                $callbackCount++;
                $domain = $opts['domain'];
                $token = $opts['value'];
                $filename = $opts['key'];

                Log::info('Challenge callback triggered', [
                    'domain' => $domain,
                    'callback' => $callbackCount . '/' . $groupSize,
                ]);

                $collectedChallenges[] = [
                    'domain' => $domain,
                    'token' => $token,
                    'filename' => $filename,
                ];

                // Save tokens to DB immediately so frontend can show them
                if ($request->challenge_type === 'dns') {
                    $tokens = array_column($collectedChallenges, 'token');
                    $request->update([
                        'challenge_token' => implode("\n", $tokens),
                        'challenge_filename' => '_acme-challenge',
                    ]);
                } else {
                    $request->update([
                        'challenge_token' => $token,
                        'challenge_filename' => $filename,
                    ]);
                }

                // If more callbacks are expected in this group, return immediately
                // so the library can fire them and the frontend shows ALL tokens at once.
                if ($callbackCount < $groupSize) {
                    Log::info('Waiting for more callbacks before polling', [
                        'domain' => $domain,
                        'collected' => $callbackCount . '/' . $groupSize,
                    ]);
                    return function ($opts) {
                        Log::info('Challenge cleanup', ['domain' => $opts['domain']]);
                    };
                }

                // Last callback in group: poll until ALL challenges are verified
                $baseDomain = $request->domain;
                $startTime = time();

                Log::info('All tokens collected, waiting for user to configure', [
                    'domain' => $baseDomain,
                    'tokens' => count($collectedChallenges),
                ]);

                while ((time() - $startTime) < self::MAX_WAIT_TIME) {
                    // Check cancellation
                    $fresh = \App\Models\CertificateRequest::find($request->id);
                    if (!$fresh || $fresh->status !== 'in_progress') {
                        Log::info('Generation cancelled by user, aborting');
                        throw new \Exception('cancelled');
                    }

                    $allVerified = true;
                    foreach ($collectedChallenges as $challenge) {
                        if ($request->challenge_type === 'dns') {
                            if (!$service->verifySingleDnsToken($baseDomain, $challenge['token'])) {
                                $allVerified = false;
                                break;
                            }
                        } else {
                            if (!$service->verifyHttpChallenge($baseDomain, $challenge['filename'], $challenge['token'])) {
                                $allVerified = false;
                                break;
                            }
                        }
                    }

                    if ($allVerified) {
                        Log::info('All tokens detected by local resolver, confirming propagation...', [
                            'domain' => $baseDomain,
                            'elapsed_seconds' => time() - $startTime,
                        ]);

                        // Wait for public DNS propagation before returning.
                        // Once we return, the library tells ACME to validate immediately.
                        // If public resolvers still have stale cache, ACME validation fails.
                        if ($request->challenge_type === 'dns') {
                            $tokens = array_column($collectedChallenges, 'token');
                            $propagated = $service->waitForDnsPropagation($baseDomain, $tokens);

                            if (!$propagated) {
                                Log::warning('DNS propagation to public resolvers incomplete, proceeding anyway', [
                                    'domain' => $baseDomain,
                                ]);
                            }
                        }

                        Log::info('All challenges verified!', [
                            'domain' => $baseDomain,
                            'elapsed_seconds' => time() - $startTime,
                        ]);
                        break;
                    }

                    Log::info('Challenges not yet verified, waiting...', [
                        'domain' => $baseDomain,
                        'elapsed_seconds' => time() - $startTime,
                    ]);

                    sleep(self::POLL_INTERVAL);
                }

                if (!$allVerified) {
                    throw new \Exception("Timeout waiting for verification for {$baseDomain}");
                }

                return function ($opts) {
                    Log::info('Challenge cleanup', ['domain' => $opts['domain']]);
                };
            };

            // Get the certificate chain - this will block in callback until verified
            $settings = $forceNewAuth ? ['authz_reuse' => false] : [];
            $certificateChain = $this->client->getCertificateChain(
                $domainKey,
                $domainConfig,
                $callback,
                $settings
            );

            // Log if the callback was skipped (authorization was cached)
            if (!$callbackCalled) {
                Log::info('Authorization was already valid (cached by ACME), no challenge needed', [
                    'domain' => $request->domain,
                ]);
            }

            if ($callbackCalled && $callbackCount < $groupSize) {
                Log::warning('Fewer callbacks than expected (partial auth reuse?)', [
                    'domain' => $request->domain,
                    'expected' => $groupSize,
                    'actual' => $callbackCount,
                ]);
            }

            // Parse the certificate to get expiration date
            $certInfo = $this->client->parseCertificate($certificateChain);
            $expiresAt = date('Y-m-d H:i:s', $certInfo['validTo_time_t']);

            // Split chain into certificate and CA bundle
            $chains = $this->client->splitChain($certificateChain);
            $caBundle = isset($chains[1]) ? implode("\n", array_slice($chains, 1)) : '';

            Log::info('Certificate generated successfully', [
                'domain' => $request->domain,
                'expires_at' => $expiresAt,
            ]);

            return [
                'success' => true,
                'certificate' => $certificateChain,
                'private_key' => $domainKey,
                'chain' => $caBundle,
                'expires_at' => $expiresAt,
            ];

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            Log::error('ACME certificate generation failed', [
                'error' => $errorMessage,
                'domain' => $request->domain,
            ]);

            return [
                'success' => false,
                'error' => $this->translateError($errorMessage, $request->challenge_type, $request->is_wildcard),
                'raw_error' => $errorMessage,
            ];
        }
    }

    /**
     * Check if a DNS token has been configured by the user.
     *
     * This is NOT a validation — Let's Encrypt handles the real validation
     * after the ACMECert library notifies the ACME server. This method only
     * detects whether the user has added the TXT record so we can stop
     * blocking the callback and let the library proceed.
     *
     * Uses PHP's dns_get_record() which queries the system resolver.
     * Per-server dig was removed because public resolvers (8.8.8.8 etc.)
     * often serve stale cached responses when new TXT records are added
     * to a domain that already has existing records.
     */
    public function verifySingleDnsToken(string $domain, string $expectedToken): bool
    {
        $record = '_acme-challenge.' . $domain;

        // Use DoH first (reliable in Docker), fall back to php dns_get_record
        $foundValues = $this->queryDnsOverHttps($record);
        if (empty($foundValues)) {
            $foundValues = $this->queryDnsWithPhp($record);
        }

        $found = in_array($expectedToken, $foundValues, true);

        if (!$found) {
            Log::info('DNS token not yet detected', [
                'record' => $record,
                'expected' => $expectedToken,
                'found' => $foundValues,
            ]);
        }

        return $found;
    }

    /**
     * Check if ALL DNS tokens have been configured by the user.
     */
    public function verifyDnsRecord(string $domain, string $expectedToken): bool
    {
        $record = '_acme-challenge.' . $domain;
        $expectedTokens = array_filter(array_map('trim', explode("\n", $expectedToken)));

        // Use DoH first (reliable in Docker), fall back to php dns_get_record
        $foundValues = $this->queryDnsOverHttps($record);
        if (empty($foundValues)) {
            $foundValues = $this->queryDnsWithPhp($record);
        }

        Log::info('DNS verification (all tokens)', [
            'record' => $record,
            'expected' => $expectedTokens,
            'found' => $foundValues,
        ]);

        if (empty($foundValues)) {
            return false;
        }

        foreach ($expectedTokens as $token) {
            if (!in_array($token, $foundValues, true)) {
                return false;
            }
        }

        return true;
    }


    // DNS over HTTPS endpoints for propagation confirmation
    private const DOH_ENDPOINTS = [
        'google' => 'https://dns.google/resolve',
        'cloudflare' => 'https://cloudflare-dns.com/dns-query',
    ];

    private const PROPAGATION_MAX_WAIT = 120; // 2 minutes max wait for propagation
    private const PROPAGATION_INTERVAL = 10;  // Check every 10 seconds

    /**
     * Wait for DNS tokens to be visible on public resolvers via DNS over HTTPS.
     *
     * After dns_get_record() detects the record (local resolver), we confirm
     * it's also visible on Google/Cloudflare DNS before returning from the
     * callback. This prevents the race condition where we tell ACME to validate
     * but their resolvers still have stale cached responses.
     */
    public function waitForDnsPropagation(string $domain, array $expectedTokens): bool
    {
        $record = '_acme-challenge.' . $domain;
        $startTime = time();

        while ((time() - $startTime) < self::PROPAGATION_MAX_WAIT) {
            $publicRecords = $this->queryDnsOverHttps($record);

            if (!empty($publicRecords)) {
                $allFound = true;
                foreach ($expectedTokens as $token) {
                    if (!in_array($token, $publicRecords, true)) {
                        $allFound = false;
                        break;
                    }
                }

                if ($allFound) {
                    Log::info('DNS propagation confirmed via public resolver', [
                        'record' => $record,
                        'elapsed_seconds' => time() - $startTime,
                    ]);
                    return true;
                }
            }

            Log::info('Waiting for DNS propagation to public resolvers...', [
                'record' => $record,
                'expected' => $expectedTokens,
                'found_public' => $publicRecords,
                'elapsed_seconds' => time() - $startTime,
            ]);

            sleep(self::PROPAGATION_INTERVAL);
        }

        return false;
    }

    /**
     * Query DNS TXT records via DNS over HTTPS (Google and Cloudflare).
     * Returns merged unique values from whichever endpoint responds.
     */
    private function queryDnsOverHttps(string $record): array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'header' => "Accept: application/dns-json\r\n",
            ],
        ]);

        $allValues = [];

        foreach (self::DOH_ENDPOINTS as $name => $baseUrl) {
            $url = $baseUrl . '?' . http_build_query([
                'name' => $record,
                'type' => 'TXT',
            ]);

            $response = @file_get_contents($url, false, $context);
            if (!$response) {
                continue;
            }

            $data = json_decode($response, true);
            if (!isset($data['Answer'])) {
                continue;
            }

            foreach ($data['Answer'] as $answer) {
                if (($answer['type'] ?? 0) !== 16) { // TXT = 16
                    continue;
                }
                $value = trim($answer['data'] ?? '', '"');
                if (!empty($value) && !in_array($value, $allValues, true)) {
                    $allValues[] = $value;
                }
            }

            // If this endpoint returned results, we have enough signal
            if (!empty($allValues)) {
                break;
            }
        }

        return $allValues;
    }

    /**
     * Query DNS TXT records using PHP's dns_get_record
     */
    private function queryDnsWithPhp(string $record): array
    {
        $foundValues = [];
        $txtRecords = @dns_get_record($record, DNS_TXT);

        if ($txtRecords) {
            foreach ($txtRecords as $txtRecord) {
                if (isset($txtRecord['txt'])) {
                    $foundValues[] = $txtRecord['txt'];
                }
            }
        }

        return $foundValues;
    }

    /**
     * Verify HTTP challenge file is accessible
     */
    public function verifyHttpChallenge(string $domain, string $filename, string $expectedContent): bool
    {
        // Handle case where filename already includes the full path
        if (str_starts_with($filename, '/.well-known/acme-challenge/')) {
            $url = "http://{$domain}{$filename}";
        } elseif (str_starts_with($filename, '.well-known/acme-challenge/')) {
            $url = "http://{$domain}/{$filename}";
        } else {
            $url = "http://{$domain}/.well-known/acme-challenge/{$filename}";
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'follow_location' => true,
                'max_redirects' => 3,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $content = @file_get_contents($url, false, $context);

        if ($content === false) {
            return false;
        }

        return trim($content) === $expectedContent;
    }

    /**
     * Check if an ACME error indicates a stale authorization that should not be retried immediately
     */
    public function isStaleAuthorizationError(string $error): bool
    {
        return stripos($error, 'authorization must be pending') !== false
            || stripos($error, 'challenge is not pending') !== false
            || stripos($error, 'No such authorization') !== false;
    }

    /**
     * Translate ACME errors to user-friendly messages
     */
    private function translateError(string $error, string $challengeType = 'http', bool $isWildcard = false): string
    {
        $verificationHint = $isWildcard || $challengeType === 'dns'
            ? __('messages.errors.hint_dns')
            : __('messages.errors.hint_http');

        // Special handling for rate limit — extract retry date from ACME error
        if (stripos($error, 'rateLimited') !== false || stripos($error, 'too many') !== false) {
            $retryAfter = null;
            if (preg_match('/retry after (\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2})/i', $error, $matches)) {
                try {
                    $retryDate = new \DateTime($matches[1], new \DateTimeZone('UTC'));
                    $retryAfter = $retryDate->format('d/m/Y H:i') . ' UTC';
                } catch (\Exception $e) {
                    // ignore parse error
                }
            }
            return $retryAfter
                ? __('messages.errors.rate_limited_acme_date', ['date' => $retryAfter])
                : __('messages.errors.rate_limited_acme');
        }

        $translations = [
            'cancelled' => '',
            'authorization must be pending' => __('messages.errors.authorization_stale'),
            'challenge is not pending' => __('messages.errors.authorization_stale'),
            'No such authorization' => __('messages.errors.authorization_stale'),
            'Challenge validation failed' => __('messages.errors.challenge_validation_failed', ['hint' => $verificationHint]),
            'DNS problem' => __('messages.errors.dns_problem'),
            'Incorrect TXT record' => __('messages.errors.incorrect_txt'),
            'Connection refused' => __('messages.errors.connection_refused'),
            'unauthorized' => __('messages.errors.unauthorized', ['hint' => $verificationHint]),
            'Timeout waiting' => $isWildcard || $challengeType === 'dns'
                ? __('messages.errors.timeout_dns')
                : __('messages.errors.timeout_http'),
        ];

        foreach ($translations as $key => $translation) {
            if (stripos($error, $key) !== false) {
                return $translation;
            }
        }

        return __('messages.errors.generic_error', ['error' => $error]);
    }
}
