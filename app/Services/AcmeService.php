<?php

namespace App\Services;

use App\Models\CertificateRequest;
use Illuminate\Support\Facades\Log;
use skoerfgen\ACMECert\ACMECert;
use skoerfgen\ACMECert\ACME_Exception;

class AcmeService
{
    private ACMECert $client;
    private string $accountKeyPath;
    private bool $staging;
    private ?\Closure $heartbeat = null;

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
            mkdir($dir, 0700, true);
        }

        // Initialize client (true = live, false = staging)
        $this->client = new ACMECert(!$this->staging);
    }

    /**
     * Called periodically while waiting, so the app can tell a live job from a killed one
     */
    public function setHeartbeat(\Closure $heartbeat): static
    {
        $this->heartbeat = $heartbeat;

        return $this;
    }

    private function beat(): void
    {
        if ($this->heartbeat) {
            ($this->heartbeat)();
        }
    }

    /**
     * Ensure we have an account key and it's registered with Let's Encrypt
     */
    private function ensureAccount(): void
    {
        // Generate account key if it doesn't exist (locked: several workers may start at once)
        if (!file_exists($this->accountKeyPath)) {
            $lock = fopen($this->accountKeyPath . '.lock', 'c');
            flock($lock, LOCK_EX);
            try {
                if (!file_exists($this->accountKeyPath)) {
                    $accountKey = $this->client->generateRSAKey(4096);
                    file_put_contents($this->accountKeyPath . '.tmp', $accountKey);
                    chmod($this->accountKeyPath . '.tmp', 0600);
                    rename($this->accountKeyPath . '.tmp', $this->accountKeyPath);
                }
            } finally {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
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
     * @param string $attempt Generation attempt id; the run aborts if the record moves to another attempt
     * @param int $deadline Unix timestamp after which waiting for the user stops
     * @return array Result with success/error and certificate data
     */
    public function generateCertificate(CertificateRequest $request, string $attempt, int $deadline, bool $forceNewAuth = false): array
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

            // Writes only land if the record still belongs to this attempt
            $saveTokens = fn (array $values) => CertificateRequest::where('id', $request->id)
                ->where('generation_attempt', $attempt)
                ->update($values);

            $callback = function ($opts) use ($request, $service, $attempt, $deadline, $saveTokens, &$collectedChallenges, &$callbackCalled, &$callbackCount, $groupSize) {
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
                    $saveTokens([
                        'challenge_token' => implode("\n", $tokens),
                        'challenge_filename' => '_acme-challenge',
                    ]);
                } else {
                    $saveTokens([
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
                $maxWait = min(self::MAX_WAIT_TIME, $deadline - $startTime);

                Log::info('All tokens collected, waiting for user to configure', [
                    'domain' => $baseDomain,
                    'tokens' => count($collectedChallenges),
                ]);

                $allVerified = false;
                while ((time() - $startTime) < $maxWait) {
                    $service->beat();

                    // Check cancellation (deleted, marked failed, or superseded by a new attempt)
                    $fresh = CertificateRequest::find($request->id);
                    if (!$fresh || !$fresh->isCurrentAttempt($attempt)) {
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
                'error' => $this->translateError($errorMessage, $request->challenge_type, $request->is_wildcard, $e instanceof ACME_Exception),
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
            $this->beat();
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

    private const HTTP_MAX_REDIRECTS = 3;
    private const HTTP_MAX_BODY = 4096;

    /**
     * Check whether the user already serves the HTTP challenge file.
     *
     * The domain is user input, so this request must not reach internal services (SSRF):
     * every hop is resolved here, pinned to a public IP (no DNS rebinding) and redirects
     * are followed manually. If the domain points to a private address we skip the
     * pre-check and let Let's Encrypt validate, which fails with its own clear error.
     */
    public function verifyHttpChallenge(string $domain, string $filename, string $expectedContent): bool
    {
        $url = "http://{$domain}/.well-known/acme-challenge/" . basename($filename);
        $result = $this->fetchPublicUrl($url);

        if ($result['status'] === 'private') {
            Log::warning('HTTP challenge target resolves to a non-public address, skipping pre-check', [
                'domain' => $domain,
                'host' => $result['host'],
            ]);
            return true;
        }

        return $result['status'] === 'ok' && trim($result['body']) === $expectedContent;
    }

    /**
     * GET a URL only through public IPs, following redirects like Let's Encrypt does (ports 80/443).
     *
     * @return array{status: 'ok'|'fail'|'private', body: string, host: string}
     */
    public function fetchPublicUrl(string $url): array
    {
        for ($hop = 0; $hop <= self::HTTP_MAX_REDIRECTS; $hop++) {
            $parts = parse_url($url);
            $scheme = strtolower($parts['scheme'] ?? '');
            $host = strtolower($parts['host'] ?? '');
            $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

            if (!in_array($scheme, ['http', 'https'], true) || $host === '' || !in_array($port, [80, 443], true)) {
                return ['status' => 'fail', 'body' => '', 'host' => $host];
            }

            // IP literals are checked as-is; hostnames are resolved
            $literal = trim($host, '[]');
            $ips = filter_var($literal, FILTER_VALIDATE_IP) ? [$literal] : $this->resolveHost($host);
            if (empty($ips)) {
                return ['status' => 'fail', 'body' => '', 'host' => $host];
            }

            $publicIps = array_values(array_filter($ips, fn ($ip) => $this->isPublicIp($ip)));
            if (empty($publicIps)) {
                return ['status' => 'private', 'body' => '', 'host' => $host];
            }

            $ip = $publicIps[0];
            $body = '';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RESOLVE => ["{$host}:{$port}:" . (str_contains($ip, ':') ? "[{$ip}]" : $ip)],
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 10,
                // Let's Encrypt accepts any certificate when a challenge redirects to HTTPS
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_USERAGENT => 'MapacheSSL challenge pre-check',
                CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$body) {
                    $body .= $chunk;
                    // Returning less than received aborts the transfer once we have enough
                    return strlen($body) > self::HTTP_MAX_BODY ? 0 : strlen($chunk);
                },
            ]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $location = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
            curl_close($ch);

            if ($code >= 300 && $code < 400 && $location) {
                $url = $location;
                continue;
            }

            return ['status' => $code === 200 ? 'ok' : 'fail', 'body' => $body, 'host' => $host];
        }

        return ['status' => 'fail', 'body' => '', 'host' => ''];
    }

    private function resolveHost(string $host): array
    {
        $records = @dns_get_record($host, DNS_A + DNS_AAAA) ?: [];
        $ips = array_filter(array_map(fn ($r) => $r['ip'] ?? $r['ipv6'] ?? null, $records));

        return array_values(array_unique($ips));
    }

    /**
     * Globally routable only: excludes private, loopback, link-local (cloud metadata), CGNAT, etc.
     */
    private function isPublicIp(string $ip): bool
    {
        // IPv4-mapped IPv6 (::ffff:127.0.0.1) must be judged by its IPv4 part
        if (preg_match('/^::ffff:(\d+\.\d+\.\d+\.\d+)$/i', $ip, $m)) {
            $ip = $m[1];
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) !== false;
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

    public function isRateLimitError(string $error): bool
    {
        return stripos($error, 'rateLimited') !== false || stripos($error, 'too many') !== false;
    }

    /**
     * Rough failure category for operator alerts and stats
     */
    public function failureKind(string $error): string
    {
        return match (true) {
            $this->isRateLimitError($error) => 'rate_limit',
            stripos($error, 'Timeout waiting') !== false => 'timeout',
            default => 'other',
        };
    }

    /**
     * Translate ACME errors to user-friendly messages
     */
    private function translateError(string $error, string $challengeType = 'http', bool $isWildcard = false, bool $fromAcme = false): string
    {
        $verificationHint = $isWildcard || $challengeType === 'dns'
            ? __('messages.errors.hint_dns')
            : __('messages.errors.hint_http');

        // Special handling for rate limit — extract retry date from ACME error
        if ($this->isRateLimitError($error)) {
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

        // Let's Encrypt problem details describe the user's own domain and help them fix it.
        // Anything else is internal (paths, network, library errors) and stays in the log.
        return $fromAcme
            ? __('messages.errors.generic_error', ['error' => mb_strimwidth($error, 0, 500, '...')])
            : __('messages.errors.internal_error');
    }
}
