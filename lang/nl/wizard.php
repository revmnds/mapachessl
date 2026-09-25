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
    'step1_wildcard_hint' => 'Vereist DNS-verificatie',
    'step1_www_notice' => 'Wildcard dekt www al.',
    'step1_www_use' => 'Gebruik',
    'step1_covers' => 'Dekt:',
    'step1_covers_and' => 'en',

    // Step 2: Email

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
    'no_refresh_warning' => 'Je kunt deze pagina sluiten.',
    'no_refresh_hint' => 'Het proces loopt door op de server en je kunt later verder in deze browser.',
    'reconnecting' => 'Geen verbinding, opnieuw proberen...',
    'queue_next' => 'Je bent de volgende in de rij.',
    'queue_ahead_one' => 'Er staat 1 persoon voor je in de rij.',
    'queue_ahead_many' => 'Er staan :count personen voor je in de rij.',
    'queue_keep_open' => 'Houd deze pagina open om je plek te behouden.',

    // Stale DNS warning
    'stale_dns_title' => 'Oude DNS-records gedetecteerd',
    'stale_dns_found' => 'Gevonden records:',
    'stale_dns_retry' => 'Ik heb ze verwijderd, controleer opnieuw',

    // Step 5: Success
    'step5_success_title' => 'Klaar',
    'step5_expires_label' => 'Verloopt',
    'step5_retention_note' => 'Download het nu: om veiligheidsredenen wordt het na 24 uur van onze server verwijderd.',
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
    'donate_text' => 'Als het je geholpen heeft, was het de moeite waard. En wil je me op een koffie trakteren, dan kan dat ook.',
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

    // Statusberichten - verificatie
    'status_still_checking' => 'Nog steeds aan het controleren...',
    'status_not_stuck' => 'Niet vastgelopen, alleen aan het wachten.',
    'status_tab_open' => 'Je kunt dit tabblad open laten en later terugkomen.',
    'status_grab_coffee' => 'Goed moment voor een kop koffie.',
    'status_tacos' => 'Technisch gezien kun je nog even een broodje halen.',
    'status_who_waits' => 'Wie wacht, versleutelt.',
    'status_spoiler' => 'Spoiler: het gaat lukken.',
    'status_stare_slower' => 'Naar het scherm staren maakt het trager. Wetenschap.',
    'status_polite_cert' => 'Je certificaat staat netjes in de rij.',
    'status_deliberate' => 'Het is niet traag, het is grondig.',
    'status_faith' => 'Nog niets, maar we geven de moed niet op.',
    'status_future_you' => 'Je toekomstige ik met HTTPS bedankt je.',

    // Alleen DNS-verificatie
    'status_dns_propagation' => 'DNS kan een paar minuten nodig hebben om bij te werken.',
    'status_dns_vantage' => 'Let\'s Encrypt controleert je DNS vanaf meerdere plekken in de wereld. Ze moeten het allemaal eens zijn.',
    'status_no_tracking' => 'De TXT-records reizen van server naar server. Zonder track-and-trace.',
    'status_dns_rules' => 'We zouden graag sneller gaan, maar DNS bepaalt het tempo.',
    'status_plot_twist' => 'Plot twist: zo lang duurt het normaal gesproken.',
    'status_dns_1983' => 'DNS stamt uit 1983. Soms merk je dat.',

    // Alleen HTTP-verificatie
    'status_http_fetching' => 'Let\'s Encrypt haalt het bestand op van je server.',
    'status_http_vantage' => 'Let\'s Encrypt controleert je server vanaf meerdere plekken in de wereld.',

    // Twijfels (na 10 minuten): helpen het probleem te vinden
    'status_doubt_dns_saved' => 'Dit duurt langer dan normaal. Controleer of het TXT-record is opgeslagen in je DNS-paneel.',
    'status_doubt_dns_name' => 'Sommige panelen voegen het domein zelf toe. Heb je de volledige naam ingevuld? Probeer dan alleen _acme-challenge.',
    'status_doubt_dns_value' => 'Controleer of de waarde geen extra spaties of aanhalingstekens bevat.',
    'status_doubt_dns_provider' => 'Gebruik je Cloudflare of een andere externe DNS? Dan hoort het record daar, niet bij je registrar.',
    'status_doubt_dns_recheck' => 'Nog steeds niets. Kijk nog eens in je DNS-paneel.',
    'status_doubt_dns_two_records' => 'Let op: het zijn twee TXT-records met dezelfde naam.',
    'status_doubt_http_url' => 'Dit duurt langer dan normaal. Open de URL van het bestand in je browser en controleer of de inhoud verschijnt.',
    'status_doubt_http_port' => 'Het bestand moet bereikbaar zijn via poort 80. Controleer of je firewall dat niet blokkeert.',
    'status_doubt_http_extension' => 'Controleer of er geen extensie, zoals .txt, aan de bestandsnaam is toegevoegd.',
    'status_doubt_http_folder' => 'Nog steeds niets. Controleer of het bestand in .well-known/acme-challenge staat.',
];
