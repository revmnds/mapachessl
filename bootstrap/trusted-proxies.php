<?php

/*
 * Proxies allowed to set X-Forwarded-For. Anyone else's header is ignored, so clients
 * can't pick their own IP to dodge the per-IP rate limits.
 *
 * Default: private networks (reverse proxy on the host or in Docker) plus Cloudflare,
 * which fronts production. Override with TRUSTED_PROXIES (comma-separated IPs/CIDRs).
 * Cloudflare ranges: https://www.cloudflare.com/ips/ (checked 2026-09-24)
 */

if ($custom = env('TRUSTED_PROXIES')) {
    return array_map('trim', explode(',', $custom));
}

return [
    // Private networks
    '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '127.0.0.1', '::1', 'fc00::/7',

    // Cloudflare IPv4
    '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
    '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
    '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
    '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',

    // Cloudflare IPv6
    '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
    '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
];
