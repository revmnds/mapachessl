<?php

return [
    // Rate Limiting
    'rate_limit' => [
        'generic' => 'Rate limit exceeded. Try again later.',
        'generate' => 'You have exceeded the certificate limit. Maximum 5 per hour. Try again later.',
        'domain' => 'This domain has exceeded the attempt limit. Maximum 3 per day.',
        'start' => 'Too many sessions started. Try again later.',
        'title' => 'Limit reached',
    ],

    // Validation errors
    'validation' => [
        'domain_required' => 'The domain is required.',
        'domain_invalid' => 'The domain is not valid.',
        'email_required' => 'The email is required.',
        'email_invalid' => 'The email is not valid.',
        'challenge_type_required' => 'Select a verification method.',
        'challenge_type_invalid' => 'Invalid verification method.',
    ],

    // System errors
    'errors' => [
        'server' => 'Server error.',
        'connection' => 'Connection error.',
        'session_expired' => 'Your session has expired. Please start again.',
        'session_not_found' => 'No active session found.',
        'certificate_failed' => 'Could not generate the certificate.',
        'download_not_available' => 'The certificate is not available for download.',
        // ACME errors
        'hint_dns' => 'Make sure the DNS TXT record is correctly configured. If you have _acme-challenge records from previous attempts, delete them before retrying.',
        'hint_http' => 'Make sure the DNS record or HTTP file is correctly configured.',
        'challenge_validation_failed' => 'Domain verification failed. :hint',
        'dns_problem' => 'DNS problem. Verify that the TXT record is correctly configured.',
        'incorrect_txt' => 'The TXT record does not match the expected value. If you have _acme-challenge records from previous attempts, delete them before retrying.',
        'connection_refused' => 'Could not connect to the server. Verify that the domain is accessible.',
        'unauthorized' => 'Unauthorized. :hint',
        'rate_limited_acme' => 'Let\'s Encrypt certificate limit reached for this domain. Try another domain or wait a few hours.',
        'rate_limited_acme_date' => 'Let\'s Encrypt certificate limit reached for this domain. You can try again after :date.',
        'timeout_dns' => 'Timed out. Configure the DNS record and try again. If you have _acme-challenge records from previous attempts, delete them before retrying.',
        'timeout_http' => 'Timed out. Configure the DNS/HTTP and try again.',
        'generation_in_progress' => 'A generation is already in progress. Wait for it to finish before trying again.',
        'authorization_stale' => 'The previous attempt left a pending authorization at Let\'s Encrypt. Wait a few minutes and start a new request.',
        'generation_stale' => 'The generation was unexpectedly interrupted. Please start a new request.',
        'stale_dns_records' => 'Old DNS TXT records from a previous attempt were found at _acme-challenge. Delete them from your DNS provider before retrying.',
        'generic_error' => 'Error completing the process: :error',
        'timeout_verification' => 'Timeout waiting for verification for :domain. Configure the DNS/HTTP and try again.',
        'zip_error' => 'Error creating ZIP file.',
    ],

    // Success
    'success' => [
        'certificate_generated' => 'Certificate generated successfully.',
        'session_started' => 'Session started.',
        'step_saved' => 'Step saved.',
    ],
];
