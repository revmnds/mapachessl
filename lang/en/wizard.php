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
    'step1_wildcard_hint' => '(requires DNS)',

    // Step 2: Email
    'step2_title' => 'Your email',
    'step2_subtitle' => 'To notify you before it expires',
    'step2_placeholder' => 'you@email.com',

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
    'no_refresh_warning' => 'Do not refresh the page',
    'no_refresh_hint' => 'or the process will restart',

    // Stale DNS warning
    'stale_dns_title' => 'Old DNS records detected',
    'stale_dns_found' => 'Records found:',
    'stale_dns_retry' => 'I already deleted them, check again',

    // Step 5: Success
    'step5_success_title' => 'Done',
    'step5_expires_label' => 'Expires',
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

    // Status phrases - verification (normal + funny)
    'status_still_checking' => 'Still checking...',
    'status_patience_virtue' => 'Patience is a virtue...',
    'status_still_on_it' => 'Still on it...',
    'status_dns_takes_time' => 'DNS can be slow, hang tight...',
    'status_keep_waiting' => 'We keep validating...',
    'status_no_worries' => 'No worries, this is normal...',
    'status_doing_fine' => 'Everything\'s fine, just waiting...',
    'status_still_here' => 'Still here, still working...',
    'status_any_moment' => 'Could be any moment now...',
    'status_propagation_slow' => 'Propagation can take a while...',
    'status_grab_coffee' => 'Good time for a coffee...',
    'status_not_stuck' => 'Not stuck, just waiting...',
    'status_servers_thinking' => 'The servers are thinking...',
    'status_almost_there' => 'Almost there...',
    'status_stretch_legs' => 'Meanwhile, have you stretched your legs?',
    'status_bits_rhythm' => 'Bits travel at their own pace...',
    'status_take_a_breath' => 'Take a breather, we\'re watching...',
    'status_internet_magic' => 'The internet is doing its magic...',
    'status_watching_plants' => 'Like watching a plant grow, but faster...',
    'status_relax' => 'Relax, we\'re on it...',
    'status_coffee_good' => 'A little coffee never hurt anyone...',
    'status_tech_needs_time' => 'Technology needs its time too...',
    'status_breathe_deep' => 'Breathe deep, almost there...',
    'status_closer_than_think' => 'Closer than you think...',
    'status_not_easy' => 'If it were easy, it wouldn\'t be fun...',
    'status_meditating' => 'No, it didn\'t freeze. It\'s just meditating...',
    'status_internet_elves' => 'The internet elves are hard at work...',
    'status_no_f5' => 'Don\'t hit F5, you\'ll make us dizzy...',
    'status_stare_slower' => 'Staring at the screen makes it slower. It\'s science.',
    'status_not_mining' => 'We promise we\'re not mining bitcoin...',
    'status_reboot_patience' => 'Have you tried turning your patience off and on again?',
    'status_dns_speed' => 'Verifying at DNS speed... which isn\'t much...',
    'status_dns_fast_joke' => 'If DNS were fast, you wouldn\'t need this message...',
    'status_convincing_servers' => 'We\'re convincing the DNS servers...',
    'status_hamsters' => 'Our hamsters are pedaling as fast as they can...',
    'status_plot_twist' => 'Plot twist: DNS really does take this long...',
    'status_spoiler' => 'Spoiler: it\'s going to work. Just give it time...',
    'status_not_a_bug' => 'This is not a bug, it\'s a patience feature...',
    'status_dns_meaning' => 'Fun fact: DNS stands for Domain Name System...',
    'status_reggaeton' => 'Random fact: an SSL cert has more lines than a pop song...',
    'status_tacos' => 'You could technically go grab lunch and come back...',
    'status_optimism' => 'Nothing yet, but optimism is free...',
    'status_mindfulness' => 'Think of this as a mindfulness exercise...',
    'status_waiting_room' => 'We\'re in the internet\'s waiting room...',
    'status_no_tracking' => 'DNS packets are on the way. No tracking number though...',
    'status_inhale_security' => 'Breathe. Inhale security, exhale HTTP...',
    'status_good_things_take' => 'Good things take time. Your cert will be great...',
    'status_billions' => 'Did you know Let\'s Encrypt has issued billions of certificates?',
    'status_polite_cert' => 'Your certificate is in line. It\'s polite and waits its turn...',
    'status_dns_rules' => 'We\'d love to go faster, but DNS makes the rules...',
    'status_who_waits' => 'Good things come to those who encrypt...',
    'status_like_chrome' => 'Processing... like your computer when you open Chrome...',
    'status_all_night' => 'We\'ve got all night. Well, 30 minutes...',
    'status_deliberate' => 'It\'s not slow, it\'s... deliberate...',
    'status_thorough' => 'Let\'s Encrypt is verifying. They\'re very thorough...',
    'status_https_1994' => 'Fun fact: HTTPS was invented by Netscape in 1994...',
    'status_each_second' => 'Every second of waiting is another second of security...',
    'status_electrons' => 'Electrons are circling the globe to verify your domain...',
    'status_faith' => 'Nothing yet, but we haven\'t lost faith...',
    'status_future_you' => 'Future you with HTTPS will thank you...',

    // Doubt phrases (appear after 10 minutes)
    'status_doubt_check_dns' => 'Are you sure you configured the DNS correctly?',
    'status_doubt_recheck' => 'Maybe double-check, just in case?',
    'status_doubt_two_records' => 'Hey... did you add both TXT records?',
    'status_doubt_spaces' => 'Did you copy them right? Sometimes an extra space...',
    'status_doubt_panel' => 'Might be worth checking your DNS panel...',
    'status_doubt_together' => 'Still nothing... shall we check together?',
    'status_doubt_longer' => 'Hmm, this is taking longer than usual...',
    'status_doubt_ttl' => 'Could your DNS TTL be set too high?',
    'status_doubt_configured' => 'No pressure, but... did you configure them yet?',
    'status_doubt_provider' => 'Maybe your DNS provider fell asleep...',
    'status_doubt_glance' => 'Just saying... a quick look at your DNS panel couldn\'t hurt...',
    'status_doubt_a_while' => 'Not to nag, but it\'s been a while...',
    'status_doubt_correct_domain' => 'Did you add the records to the right domain?',
    'status_doubt_trailing_space' => 'Check for spaces at the start or end of the TXT value...',
    'status_doubt_slow_provider' => 'Is your DNS provider one of the slow ones? Honest question...',
];
