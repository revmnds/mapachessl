<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mensajes generales - Rate limiting, errores y notificaciones
    |--------------------------------------------------------------------------
    */

    // Rate Limiting
    'rate_limit' => [
        'generic' => 'Límite de solicitudes excedido. Intenta más tarde.',
        'generate' => 'Has excedido el límite de certificados. Máximo 5 por hora. Intenta más tarde.',
        'domain' => 'Este dominio ha excedido el límite de intentos. Máximo 3 por día.',
        'start' => 'Demasiadas sesiones iniciadas. Intenta más tarde.',
        'title' => 'Límite alcanzado',
    ],

    // Errores de validación
    'validation' => [
        'domain_required' => 'El dominio es requerido.',
        'domain_invalid' => 'El dominio no es válido.',
        'email_required' => 'El email es requerido.',
        'email_invalid' => 'El email no es válido.',
        'challenge_type_required' => 'Selecciona un método de verificación.',
        'challenge_type_invalid' => 'Método de verificación no válido.',
    ],

    // Errores de sistema
    'errors' => [
        'server' => 'Error del servidor.',
        'connection' => 'Error de conexión.',
        'session_expired' => 'Tu sesión ha expirado. Por favor, inicia de nuevo.',
        'session_not_found' => 'No se encontró una sesión activa.',
        'certificate_failed' => 'No se pudo generar el certificado.',
        'download_not_available' => 'El certificado no está disponible para descarga.',
        // ACME errors
        'hint_dns' => 'Asegúrate de que el registro DNS TXT esté correctamente configurado. Si tienes registros _acme-challenge de intentos anteriores, elimínalos antes de reintentar.',
        'hint_http' => 'Asegúrate de que el registro DNS o archivo HTTP esté correctamente configurado.',
        'challenge_validation_failed' => 'La verificación del dominio falló. :hint',
        'dns_problem' => 'Problema de DNS. Verifica que el registro TXT esté correctamente configurado.',
        'incorrect_txt' => 'El registro TXT no coincide con el valor esperado. Si tienes registros _acme-challenge de intentos anteriores, elimínalos antes de reintentar.',
        'connection_refused' => 'No se pudo conectar al servidor. Verifica que el dominio sea accesible.',
        'unauthorized' => 'No autorizado. :hint',
        'rate_limited_acme' => 'Se alcanzó el límite de certificados de Let\'s Encrypt para este dominio. Intenta con otro dominio o espera unas horas.',
        'rate_limited_acme_date' => 'Se alcanzó el límite de certificados de Let\'s Encrypt para este dominio. Podrás intentar de nuevo después del :date.',
        'timeout_dns' => 'Se agotó el tiempo de espera. Configura el registro DNS y vuelve a intentar. Si tienes registros _acme-challenge de intentos anteriores, elimínalos antes de reintentar.',
        'timeout_http' => 'Se agotó el tiempo de espera. Configura el DNS/HTTP y vuelve a intentar.',
        'generation_in_progress' => 'Ya hay una generación en curso. Espera a que termine antes de intentar de nuevo.',
        'authorization_stale' => 'El intento anterior dejó una autorización pendiente en Let\'s Encrypt. Espera unos minutos e inicia una nueva solicitud.',
        'generation_stale' => 'La generación se interrumpió inesperadamente. Por favor, inicia una nueva solicitud.',
        'stale_dns_records' => 'Se encontraron registros TXT antiguos de un intento anterior en _acme-challenge. Elimínalos de tu proveedor DNS antes de reintentar.',
        'generic_error' => 'Error al completar el proceso: :error',
        'timeout_verification' => 'Timeout esperando verificación para :domain. Configura el DNS/HTTP y vuelve a intentar.',
        'zip_error' => 'Error al crear el archivo ZIP.',
    ],

    // Éxito
    'success' => [
        'certificate_generated' => 'Certificado generado exitosamente.',
        'session_started' => 'Sesión iniciada.',
        'step_saved' => 'Paso guardado.',
    ],
];
