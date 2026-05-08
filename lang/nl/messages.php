<?php

return [
    // Rate Limiting
    'rate_limit' => [
        'generic' => 'Limiet voor verzoeken overschreden. Probeer het later opnieuw.',
        'generate' => 'Je hebt de certificaatlimiet overschreden. Maximaal 5 per uur. Probeer het later opnieuw.',
        'domain' => 'Dit domein heeft de pogingslimiet overschreden. Maximaal 3 per dag.',
        'start' => 'Te veel sessies gestart. Probeer het later opnieuw.',
        'title' => 'Limiet bereikt',
    ],

    // Validation errors
    'validation' => [
        'domain_required' => 'Het domein is verplicht.',
        'domain_invalid' => 'Het domein is niet geldig.',
        'email_required' => 'Het e-mailadres is verplicht.',
        'email_invalid' => 'Het e-mailadres is niet geldig.',
        'challenge_type_required' => 'Selecteer een verificatiemethode.',
        'challenge_type_invalid' => 'Ongeldige verificatiemethode.',
    ],

    // System errors
    'errors' => [
        'server' => 'Serverfout.',
        'connection' => 'Verbindingsfout.',
        'session_expired' => 'Je sessie is verlopen. Begin opnieuw.',
        'session_not_found' => 'Geen actieve sessie gevonden.',
        'certificate_failed' => 'Kon het certificaat niet genereren.',
        'download_not_available' => 'Het certificaat is niet beschikbaar om te downloaden.',
        // ACME errors
        'hint_dns' => 'Zorg ervoor dat het DNS TXT-record correct is geconfigureerd. Als je _acme-challenge records hebt van eerdere pogingen, verwijder deze voordat je het opnieuw probeert.',
        'hint_http' => 'Zorg ervoor dat het DNS-record of HTTP-bestand correct is geconfigureerd.',
        'challenge_validation_failed' => 'Domeinverificatie mislukt. :hint',
        'dns_problem' => 'DNS-probleem. Controleer of het TXT-record correct is geconfigureerd.',
        'incorrect_txt' => 'Het TXT-record komt niet overeen met de verwachte waarde. Als je _acme-challenge records hebt van eerdere pogingen, verwijder deze voordat je het opnieuw probeert.',
        'connection_refused' => 'Kon geen verbinding maken met de server. Controleer of het domein bereikbaar is.',
        'unauthorized' => 'Niet geautoriseerd. :hint',
        'rate_limited_acme' => 'Let\'s Encrypt certificaatlimiet bereikt voor dit domein. Probeer een ander domein of wacht een paar uur.',
        'rate_limited_acme_date' => 'Let\'s Encrypt certificaatlimiet bereikt voor dit domein. Je kunt het opnieuw proberen na :date.',
        'timeout_dns' => 'Tijd verstreken. Configureer het DNS-record en probeer het opnieuw. Als je _acme-challenge records hebt van eerdere pogingen, verwijder deze voordat je het opnieuw probeert.',
        'timeout_http' => 'Tijd verstreken. Configureer het DNS/HTTP en probeer het opnieuw.',
        'generation_in_progress' => 'Er is al een generatie bezig. Wacht tot deze is voltooid voordat je het opnieuw probeert.',
        'authorization_stale' => 'De vorige poging heeft een autorisatie achtergelaten bij Let\'s Encrypt. Wacht een paar minuten en start een nieuw verzoek.',
        'generation_stale' => 'De generatie werd onverwacht onderbroken. Start een nieuw verzoek.',
        'stale_dns_records' => 'Er zijn oude DNS TXT-records van een vorige poging gevonden bij _acme-challenge. Verwijder ze bij je DNS-provider voordat je het opnieuw probeert.',
        'generic_error' => 'Fout bij het voltooien van het proces: :error',
        'timeout_verification' => 'Timeout bij wachten op verificatie voor :domain. Configureer het DNS/HTTP en probeer het opnieuw.',
        'zip_error' => 'Fout bij het maken van het ZIP-bestand.',
    ],

    // Success
    'success' => [
        'certificate_generated' => 'Certificaat succesvol gegenereerd.',
        'session_started' => 'Sessie gestart.',
        'step_saved' => 'Stap opgeslagen.',
    ],
];
