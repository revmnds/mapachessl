<?php

return [
    // General
    'app_name' => 'MapacheSSL',
    'tagline' => 'Gratis SSL-certificaten met Let\'s Encrypt',
    'slogan' => 'Geen registratie. Geen kosten. Geen gedoe.',

    // Common buttons
    'btn_start' => 'Beginnen',
    'btn_starting' => 'Starten...',
    'btn_continue' => 'Doorgaan',
    'btn_saving' => 'Opslaan...',
    'btn_back' => 'Terug',
    'btn_cancel' => 'Annuleren',
    'btn_generate' => 'Genereren',
    'btn_retry' => 'Opnieuw proberen',
    'btn_new_certificate' => 'Nieuw certificaat',
    'btn_download_zip' => 'ZIP downloaden',

    // Step 1: Domain
    'step1_title' => 'Jouw domein',
    'step1_subtitle' => 'Het domein voor het SSL-certificaat',
    'step1_placeholder' => 'voorbeeld.nl',
    'step1_wildcard_label' => 'Subdomeinen opnemen',
    'step1_wildcard_hint' => '(vereist DNS)',

    // Step 2: Email
    'step2_title' => 'Jouw e-mail',
    'step2_subtitle' => 'Om je te waarschuwen voordat het verloopt',
    'step2_placeholder' => 'jij@email.nl',

    // Step 3: Verification
    'step3_title' => 'Verificatie',
    'step3_subtitle_wildcard' => 'Wildcard-certificaten vereisen DNS-verificatie',
    'step3_subtitle_normal' => 'Kies hoe je jouw domein wilt verifiëren',
    'step3_http_title' => 'HTTP-bestand',
    'step3_http_desc' => 'Upload een bestand naar je server',
    'step3_dns_title' => 'DNS-record',
    'step3_dns_desc' => 'Voeg een TXT-record toe',

    // Step 4: Generate
    'step4_title' => 'Certificaat genereren',
    'step4_subtitle' => 'Klik op "Genereren" om het proces te starten. De verificatie-instructies worden getoond.',
    'step4_how_title' => 'Hoe werkt het?',
    'step4_how_step1' => 'Klik op "Genereren"',
    'step4_how_step2_dns' => 'De DNS-records die je moet configureren verschijnen',
    'step4_how_step2_http' => 'Het bestand dat je naar je server moet uploaden verschijnt',
    'step4_how_step3' => 'Configureer de verificatie (je hebt maximaal 30 minuten)',
    'step4_how_step4' => 'Het systeem verifieert automatisch en genereert het certificaat',

    // Step 4: Token configuration
    'step4_config_title' => 'Configureer',
    'step4_config_http_subtitle' => 'Upload dit bestand naar je server',
    'step4_config_dns_subtitle' => 'Voeg dit DNS-record toe',
    'step4_getting_tokens' => 'Verificatietokens ophalen...',

    // Verification field labels
    'label_path' => 'Pad',
    'label_file' => 'Bestand',
    'label_content' => 'Inhoud',
    'label_host' => 'Host',
    'label_type' => 'Type',
    'label_ttl' => 'TTL',
    'label_value' => 'Waarde',

    // Wildcard
    'wildcard_title' => 'Wildcard-certificaat',
    'wildcard_notice' => 'Je moet',
    'wildcard_records' => 'TXT-records toevoegen met dezelfde naam maar verschillende waarden.',
    'wildcard_sequential' => 'Wildcard-certificaten kunnen 1 of 2 TXT-records vereisen. Configureer elk record zodra het verschijnt.',

    // Verification status
    'verification_pending_title' => 'Verificatie in afwachting',
    'verification_waiting_title' => 'Wachten op verificatie...',
    'verification_waiting_dns' => 'Configureer de DNS-records hierboven. Het systeem zal automatisch verifiëren.',
    'verification_waiting_http' => 'Upload het bestand naar je server. Het systeem zal automatisch verifiëren.',

    // No refresh warning
    'no_refresh_warning' => 'Vernieuw de pagina niet',
    'no_refresh_hint' => 'anders wordt het proces opnieuw gestart',

    // Stale DNS warning
    'stale_dns_title' => 'Oude DNS-records gedetecteerd',
    'stale_dns_found' => 'Gevonden records:',
    'stale_dns_retry' => 'Ik heb ze verwijderd, controleer opnieuw',

    // Step 5: Success
    'step5_success_title' => 'Klaar',
    'step5_expires_label' => 'Verloopt',
    'step5_includes_label' => 'Bevat',
    'step5_includes_value' => 'Certificaat, sleutel, keten',

    // Support / Donations
    'support_message' => 'Was het nuttig? Steun het project',

    // Copyable certificates
    'cert_view_title' => 'Certificaten bekijken',
    'cert_view_subtitle' => 'Kopieer direct of download de ZIP',
    'cert_tab_fullchain' => 'Fullchain',
    'cert_tab_certificate' => 'Certificaat',
    'cert_tab_private_key' => 'Privésleutel',
    'cert_tab_chain' => 'Keten',
    'cert_copy_btn' => 'Kopiëren',
    'cert_copied' => 'Gekopieerd!',
    'cert_warning_private_key' => 'Bewaar deze sleutel veilig. Deel hem niet.',

    // Step 5: Error
    'step5_error_title' => 'Fout',
    'step5_error_subtitle' => 'Kon het certificaat niet genereren',

    // Toast and feedback
    'toast_copied' => 'Gekopieerd!',

    // Connection errors
    'error_connection_interrupted' => 'De verbinding is onderbroken. Herlaad de pagina om de huidige status te zien.',
    'error_connection_failed' => 'Verbindingsfout. Herlaad de pagina om de huidige status te zien.',
    'error_server_prefix' => 'Serverfout: ',

    // Statusberichten - token generatie
    'status_contacting_acme' => 'Verbinden met Let\'s Encrypt...',
    'status_requesting_challenge' => 'Challenge-tokens aanvragen...',
    'status_preparing_validation' => 'Domeinvalidatie voorbereiden...',
    'status_generating_keys' => 'Cryptografische sleutels genereren...',
    'status_securing_channel' => 'Communicatiekanaal beveiligen...',
    'status_registering_domain' => 'Je domein registreren...',
    'status_almost_ready' => 'Bijna klaar...',

    // Statusberichten - verificatie (normaal + grappig)
    'status_still_checking' => 'Nog steeds aan het controleren...',
    'status_patience_virtue' => 'Geduld is een schone zaak...',
    'status_still_on_it' => 'We zijn er nog mee bezig...',
    'status_dns_takes_time' => 'DNS kan traag zijn, even geduld...',
    'status_keep_waiting' => 'We blijven valideren...',
    'status_no_worries' => 'Geen zorgen, dit is normaal...',
    'status_doing_fine' => 'Alles goed, gewoon wachten...',
    'status_still_here' => 'Nog steeds bezig...',
    'status_any_moment' => 'Kan elk moment nu zijn...',
    'status_propagation_slow' => 'Propagatie kan even duren...',
    'status_grab_coffee' => 'Goed moment voor een koffie...',
    'status_not_stuck' => 'Niet vastgelopen, gewoon wachten...',
    'status_servers_thinking' => 'De servers zijn aan het nadenken...',
    'status_almost_there' => 'Bijna daar...',
    'status_stretch_legs' => 'Ondertussen, al even de benen gestrekt?',
    'status_bits_rhythm' => 'Bits reizen op hun eigen tempo...',
    'status_take_a_breath' => 'Neem even pauze, wij houden de wacht...',
    'status_internet_magic' => 'Het internet doet zijn magie...',
    'status_watching_plants' => 'Zoals een plant zien groeien, maar sneller...',
    'status_relax' => 'Ontspan, we zijn ermee bezig...',
    'status_coffee_good' => 'Een kopje koffie kan nooit kwaad...',
    'status_tech_needs_time' => 'Technologie heeft ook zijn tijd nodig...',
    'status_breathe_deep' => 'Diep ademhalen, bijna klaar...',
    'status_closer_than_think' => 'Dichterbij dan je denkt...',
    'status_not_easy' => 'Als het makkelijk was, was het niet leuk...',
    'status_meditating' => 'Nee, het hangt niet. Het mediteert...',
    'status_internet_elves' => 'De internetkabouters zijn hard aan het werk...',
    'status_no_f5' => 'Druk niet op F5, dan worden we duizelig...',
    'status_stare_slower' => 'Naar het scherm staren maakt het langzamer. Wetenschap.',
    'status_not_mining' => 'We beloven dat we geen bitcoin aan het minen zijn...',
    'status_reboot_patience' => 'Heb je geprobeerd je geduld uit en aan te zetten?',
    'status_dns_speed' => 'Verifiëren op DNS-snelheid... wat niet veel is...',
    'status_dns_fast_joke' => 'Als DNS snel was, had je dit bericht niet nodig...',
    'status_convincing_servers' => 'We overtuigen de DNS-servers...',
    'status_hamsters' => 'Onze hamsters trappen zo hard als ze kunnen...',
    'status_plot_twist' => 'Plot twist: DNS duurt echt zo lang...',
    'status_spoiler' => 'Spoiler: het gaat lukken. Geef het even tijd...',
    'status_not_a_bug' => 'Dit is geen bug, het is een geduld-feature...',
    'status_dns_meaning' => 'Wist je dat DNS staat voor Domain Name System?',
    'status_reggaeton' => 'Feitje: een SSL-certificaat heeft meer regels dan een popliedje...',
    'status_tacos' => 'Je zou technisch gezien even een broodje kunnen halen...',
    'status_optimism' => 'Nog niets, maar optimisme is gratis...',
    'status_mindfulness' => 'Zie dit als een mindfulness-oefening...',
    'status_waiting_room' => 'We zitten in de wachtkamer van het internet...',
    'status_no_tracking' => 'DNS-pakketten zijn onderweg. Zonder tracking helaas...',
    'status_inhale_security' => 'Adem in. Veiligheid erin, HTTP eruit...',
    'status_good_things_take' => 'Goed ding wil tijd hebben. Je certificaat wordt top...',
    'status_billions' => 'Wist je dat Let\'s Encrypt miljarden certificaten heeft uitgegeven?',
    'status_polite_cert' => 'Je certificaat staat in de rij. Het is beleefd en wacht op zijn beurt...',
    'status_dns_rules' => 'We zouden sneller willen, maar DNS bepaalt het tempo...',
    'status_who_waits' => 'Wie wacht, versleutelt...',
    'status_like_chrome' => 'Verwerken... zoals je computer wanneer je Chrome opent...',
    'status_all_night' => 'We hebben de hele nacht. Nou ja, 30 minuten...',
    'status_deliberate' => 'Het is niet traag, het is... weloverwogen...',
    'status_thorough' => 'Let\'s Encrypt is aan het verifiëren. Ze zijn erg grondig...',
    'status_https_1994' => 'Feitje: HTTPS werd uitgevonden door Netscape in 1994...',
    'status_each_second' => 'Elke seconde wachten is een seconde meer veiligheid...',
    'status_electrons' => 'Elektronen cirkelen de wereld rond om je domein te verifiëren...',
    'status_faith' => 'Nog niets, maar we hebben het geloof niet verloren...',
    'status_future_you' => 'Je toekomstige zelf met HTTPS zal je dankbaar zijn...',

    // Twijfelberichten (verschijnen na 10 minuten)
    'status_doubt_check_dns' => 'Weet je zeker dat je de DNS goed hebt geconfigureerd?',
    'status_doubt_recheck' => 'Misschien nog even dubbelchecken, voor de zekerheid?',
    'status_doubt_two_records' => 'Hé... heb je beide TXT-records toegevoegd?',
    'status_doubt_spaces' => 'Goed gekopieerd? Soms een spatie te veel...',
    'status_doubt_panel' => 'Misschien even je DNS-paneel checken...',
    'status_doubt_together' => 'Nog steeds niets... zullen we samen kijken?',
    'status_doubt_longer' => 'Hmm, dit duurt langer dan normaal...',
    'status_doubt_ttl' => 'Zou je DNS TTL te hoog staan?',
    'status_doubt_configured' => 'Geen druk, maar... heb je ze al geconfigureerd?',
    'status_doubt_provider' => 'Misschien is je DNS-provider in slaap gevallen...',
    'status_doubt_glance' => 'Zeg ik maar... even snel je DNS-paneel bekijken kan geen kwaad...',
    'status_doubt_a_while' => 'Niet om te zeuren, maar het duurt al even...',
    'status_doubt_correct_domain' => 'Heb je de records bij het juiste domein gezet?',
    'status_doubt_trailing_space' => 'Check op spaties aan het begin of einde van de TXT-waarde...',
    'status_doubt_slow_provider' => 'Is je DNS-provider een van de trage? Eerlijke vraag...',
];
