<?php

return [
    // General
    'app_name' => 'MapacheSSL',
    'tagline' => 'Free SSL certificates with Let\'s Encrypt',
    'slogan' => 'No registration. No cost. No hassle.',

    // Common buttons
    'btn_start' => 'Get Started',
    'btn_starting' => 'Starting...',
    'btn_continue' => 'Continue',
    'btn_saving' => 'Saving...',
    'btn_back' => 'Back',
    'btn_cancel' => 'Cancel',
    'btn_generate' => 'Generate',
    'btn_retry' => 'Retry',
    'btn_new_certificate' => 'New certificate',
    'btn_download_zip' => 'Download ZIP',

    // Step 1: Domain
    'step1_title' => 'Your domain',
    'step1_subtitle' => 'The domain for the SSL certificate',
    'step1_placeholder' => 'example.com',
    'step1_wildcard_label' => 'Include subdomains',
    'step1_wildcard_hint' => 'Requires DNS verification',
    'step1_www_notice' => 'Wildcard already covers www.',
    'step1_www_use' => 'Use',
    'step1_covers' => 'Covers:',
    'step1_covers_and' => 'and',

    // Step 2: Email

    // Step 3: Verification
    'step3_title' => 'Verification',
    'step3_subtitle_wildcard' => 'Wildcard certificates require DNS verification',
    'step3_subtitle_normal' => 'Choose how to verify your domain',
    'step3_http_title' => 'HTTP File',
    'step3_http_desc' => 'Upload a file to your server',
    'step3_dns_title' => 'DNS Record',
    'step3_dns_desc' => 'Add a TXT record',

    // Step 4: Generate
    'step4_title' => 'Generate certificate',
    'step4_subtitle' => 'Click "Generate" to start the process. Verification instructions will be shown.',
    'step4_how_title' => 'How does it work?',
    'step4_how_step1' => 'Click "Generate"',
    'step4_how_step2_dns' => 'The DNS records you need to configure will appear',
    'step4_how_step2_http' => 'The file you need to upload to your server will appear',
    'step4_how_step3' => 'Configure the verification (you have up to 30 minutes)',
    'step4_how_step4' => 'The system will automatically verify and generate the certificate',

    // Step 4: Token configuration
    'step4_config_title' => 'Configure',
    'step4_config_http_subtitle' => 'Upload this file to your server',
    'step4_config_dns_subtitle' => 'Add this DNS record',
    'step4_getting_tokens' => 'Getting verification tokens...',

    // Verification field labels
    'label_path' => 'Path',
    'label_file' => 'File',
    'label_content' => 'Content',
    'label_host' => 'Host',
    'label_type' => 'Type',
    'label_ttl' => 'TTL',
    'label_value' => 'Value',

    // Wildcard
    'wildcard_title' => 'Wildcard Certificate',
    'wildcard_notice' => 'You must add',
    'wildcard_records' => 'TXT records with the same name but different values.',
    'wildcard_sequential' => 'Wildcard certificates may require 1 or 2 TXT records. Configure each one as it appears.',

    // Verification status
    'verification_pending_title' => 'Verification pending',
    'verification_waiting_title' => 'Waiting for verification...',
    'verification_waiting_dns' => 'Configure the DNS records above. The system will verify automatically.',
    'verification_waiting_http' => 'Upload the file to your server. The system will verify automatically.',

    // No refresh warning
    'no_refresh_warning' => 'You can close this page.',
    'no_refresh_hint' => 'The process keeps running on the server and you can pick it up again from this browser.',
    'reconnecting' => 'Connection lost, retrying...',
    'queue_next' => 'You\'re next in line.',
    'queue_ahead_one' => 'There is 1 person ahead of you in line.',
    'queue_ahead_many' => 'There are :count people ahead of you in line.',
    'queue_keep_open' => 'Keep this page open to hold your place.',

    // Stale DNS warning
    'stale_dns_title' => 'Old DNS records detected',
    'stale_dns_found' => 'Records found:',
    'stale_dns_retry' => 'I already deleted them, check again',

    // Step 5: Success
    'step5_success_title' => 'Done',
    'step5_expires_label' => 'Expires',
    'step5_retention_note' => 'Download it now: for security it is deleted from our server after 24 hours.',
    'step5_includes_label' => 'Includes',
    'step5_includes_value' => 'Certificate, key, chain',

    // Support / Donations
    'support_message' => 'Was it useful? Support the project',

    // Copyable certificates
    'cert_view_title' => 'View certificates',
    'cert_view_subtitle' => 'Copy directly or download the ZIP',
    'cert_tab_fullchain' => 'Fullchain',
    'cert_tab_certificate' => 'Certificate',
    'cert_tab_private_key' => 'Private key',
    'cert_tab_chain' => 'Chain',
    'cert_copy_btn' => 'Copy',
    'cert_copied' => 'Copied!',
    'donate_text' => 'If it helped, it was worth it. And if you feel like buying me a coffee, that works too.',
    'cert_warning_private_key' => 'Keep this key secure. Do not share it.',

    // Step 5: Error
    'step5_error_title' => 'Error',
    'step5_error_subtitle' => 'Could not generate the certificate',

    // Toast and feedback
    'toast_copied' => 'Copied!',

    // Connection errors
    'error_connection_interrupted' => 'The connection was interrupted. Reload the page to see the current status.',
    'error_connection_failed' => 'Connection error. Reload the page to see the current status.',
    'error_server_prefix' => 'Server error: ',

    // Status phrases - token generation
    'status_contacting_acme' => 'Contacting Let\'s Encrypt...',
    'status_requesting_challenge' => 'Requesting challenge tokens...',
    'status_preparing_validation' => 'Preparing domain validation...',
    'status_generating_keys' => 'Generating cryptographic keys...',
    'status_securing_channel' => 'Securing communication channel...',
    'status_registering_domain' => 'Registering your domain...',
    'status_almost_ready' => 'Almost ready...',

    // Status phrases - verification
    'status_still_checking' => 'Still checking...',
    'status_not_stuck' => 'Not stuck, just waiting.',
    'status_tab_open' => 'You can leave this tab open and come back later.',
    'status_grab_coffee' => 'Good time for a coffee.',
    'status_tacos' => 'You could technically go grab tacos and be back in time.',
    'status_who_waits' => 'Good things come to those who encrypt.',
    'status_spoiler' => 'Spoiler: it\'s going to work.',
    'status_stare_slower' => 'Staring at the screen makes it slower. It\'s science.',
    'status_polite_cert' => 'Your certificate is waiting in line. Very polite.',
    'status_deliberate' => 'It\'s not slow, it\'s thorough.',
    'status_faith' => 'Nothing yet, but we haven\'t lost faith.',
    'status_future_you' => 'Future you, with HTTPS, says thanks.',

    // DNS verification only
    'status_dns_propagation' => 'DNS can take a few minutes to update.',
    'status_dns_vantage' => 'Let\'s Encrypt checks your DNS from several places around the world. They all have to agree.',
    'status_no_tracking' => 'The TXT records are hopping from server to server. No tracking number.',
    'status_dns_rules' => 'We\'d love to go faster, but DNS makes the rules.',
    'status_plot_twist' => 'Plot twist: this is how long it normally takes.',
    'status_dns_1983' => 'DNS dates back to 1983. Sometimes it shows.',

    // HTTP verification only
    'status_http_fetching' => 'Let\'s Encrypt is fetching the file from your server.',
    'status_http_vantage' => 'Let\'s Encrypt checks your server from several places around the world.',

    // Doubts (after 10 minutes): help find the problem
    'status_doubt_dns_saved' => 'This is taking longer than usual. Check that the TXT record is saved in your DNS panel.',
    'status_doubt_dns_name' => 'Some panels append the domain on their own. If you entered the full name, try just _acme-challenge.',
    'status_doubt_dns_value' => 'Check that the value has no extra spaces or quotes.',
    'status_doubt_dns_provider' => 'If you use Cloudflare or another external DNS, the record goes there, not where you bought the domain.',
    'status_doubt_dns_recheck' => 'Still nothing. Worth another look at your DNS panel.',
    'status_doubt_dns_two_records' => 'Remember: it\'s two TXT records with the same name.',
    'status_doubt_http_url' => 'This is taking longer than usual. Open the file URL in your browser and check that it shows the content.',
    'status_doubt_http_port' => 'The file has to be reachable on port 80. Check that your firewall isn\'t blocking it.',
    'status_doubt_http_extension' => 'Check that no extension, like .txt, was added to the file name.',
    'status_doubt_http_folder' => 'Still nothing. Check that the file is inside .well-known/acme-challenge.',
];
