<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Wizard - Textos de la interfaz del asistente SSL
    |--------------------------------------------------------------------------
    */

    // General
    'app_name' => 'MapacheSSL',
    'tagline' => 'Certificados SSL gratuitos con Let\'s Encrypt',
    'slogan' => 'Sin registro. Sin costo. Sin complicaciones.',

    // Botones comunes
    'btn_start' => 'Comenzar',
    'btn_starting' => 'Iniciando...',
    'btn_continue' => 'Continuar',
    'btn_saving' => 'Guardando...',
    'btn_back' => 'Atrás',
    'btn_cancel' => 'Cancelar',
    'btn_generate' => 'Generar',
    'btn_retry' => 'Reintentar',
    'btn_new_certificate' => 'Nuevo certificado',
    'btn_download_zip' => 'Descargar ZIP',

    // Step 1: Dominio
    'step1_title' => 'Tu dominio',
    'step1_subtitle' => 'El dominio para el certificado SSL',
    'step1_placeholder' => 'ejemplo.com',
    'step1_wildcard_label' => 'Incluir subdominios',
    'step1_wildcard_hint' => 'Requiere verificación DNS',
    'step1_www_notice' => 'El wildcard ya incluye www.',
    'step1_www_use' => 'Usar',
    'step1_covers' => 'Cubre:',
    'step1_covers_and' => 'y',

    // Step 2: Email

    // Step 3: Verificación
    'step3_title' => 'Verificación',
    'step3_subtitle_wildcard' => 'Los certificados wildcard requieren verificación DNS',
    'step3_subtitle_normal' => 'Elige cómo verificar tu dominio',
    'step3_http_title' => 'Archivo HTTP',
    'step3_http_desc' => 'Sube un archivo a tu servidor',
    'step3_dns_title' => 'Registro DNS',
    'step3_dns_desc' => 'Agrega un registro TXT',

    // Step 4: Generar
    'step4_title' => 'Generar certificado',
    'step4_subtitle' => 'Haz clic en "Generar" para iniciar el proceso. Se mostrarán las instrucciones de verificación.',
    'step4_how_title' => '¿Cómo funciona?',
    'step4_how_step1' => 'Haz clic en "Generar"',
    'step4_how_step2_dns' => 'Aparecerán los registros DNS que debes configurar',
    'step4_how_step2_http' => 'Aparecerá el archivo que debes subir a tu servidor',
    'step4_how_step3' => 'Configura la verificación (tienes hasta 30 minutos)',
    'step4_how_step4' => 'El sistema verificará automáticamente y generará el certificado',

    // Step 4: Configuración de tokens
    'step4_config_title' => 'Configura',
    'step4_config_http_subtitle' => 'Sube este archivo a tu servidor',
    'step4_config_dns_subtitle' => 'Agrega este registro DNS',
    'step4_getting_tokens' => 'Obteniendo tokens de verificación...',

    // Labels para campos de verificación
    'label_path' => 'Ruta',
    'label_file' => 'Archivo',
    'label_content' => 'Contenido',
    'label_host' => 'Host',
    'label_type' => 'Tipo',
    'label_ttl' => 'TTL',
    'label_value' => 'Valor',

    // Wildcard
    'wildcard_title' => 'Certificado Wildcard',
    'wildcard_notice' => 'Debes agregar',
    'wildcard_records' => 'registros TXT con el mismo nombre pero diferentes valores.',
    'wildcard_sequential' => 'Los certificados wildcard pueden requerir 1 o 2 registros TXT. Configura cada uno conforme aparezca.',

    // Estados de verificación
    'verification_pending_title' => 'Verificación pendiente',
    'verification_waiting_title' => 'Esperando verificación...',
    'verification_waiting_dns' => 'Configura los registros DNS arriba. El sistema verificará automáticamente.',
    'verification_waiting_http' => 'Sube el archivo a tu servidor. El sistema verificará automáticamente.',

    // Aviso de no refrescar
    'no_refresh_warning' => 'Puedes cerrar esta página.',
    'no_refresh_hint' => 'El proceso sigue en el servidor y lo retomas al volver desde este navegador.',
    'reconnecting' => 'Sin conexión, reintentando...',
    'queue_next' => 'Eres el siguiente en la fila.',
    'queue_ahead_one' => 'Hay 1 persona antes que tú en la fila.',
    'queue_ahead_many' => 'Hay :count personas antes que tú en la fila.',
    'queue_keep_open' => 'Mantén esta página abierta para no perder tu lugar.',

    // Aviso de DNS viejo
    'stale_dns_title' => 'Registros DNS antiguos detectados',
    'stale_dns_found' => 'Registros encontrados:',
    'stale_dns_retry' => 'Ya los borré, verificar de nuevo',

    // Step 5: Éxito
    'step5_success_title' => 'Listo',
    'step5_expires_label' => 'Expira',
    'step5_retention_note' => 'Descárgalo ahora: por seguridad se borra de nuestro servidor en 24 horas.',
    'step5_includes_label' => 'Incluye',
    'step5_includes_value' => 'Certificado, llave, cadena',

    // Soporte / Donaciones
    'support_message' => '¿Te fue útil? Apoya el proyecto',

    // Certificados copiables
    'cert_view_title' => 'Ver certificados',
    'cert_view_subtitle' => 'Copia directamente o descarga el ZIP',
    'cert_tab_fullchain' => 'Fullchain',
    'cert_tab_certificate' => 'Certificado',
    'cert_tab_private_key' => 'Llave privada',
    'cert_tab_chain' => 'Cadena',
    'cert_copy_btn' => 'Copiar',
    'cert_copied' => '¡Copiado!',
    'donate_text' => 'Si te sirvió, ya valió la pena. Y si quieres invitar un café, también se vale.',
    'cert_warning_private_key' => 'Mantén esta llave segura. No la compartas.',

    // Step 5: Error
    'step5_error_title' => 'Error',
    'step5_error_subtitle' => 'No se pudo generar el certificado',

    // Toast y feedback
    'toast_copied' => '¡Copiado!',

    // Errores de conexión
    'error_connection_interrupted' => 'La conexión se interrumpió. Recarga la página para ver el estado actual.',
    'error_connection_failed' => 'Error de conexión. Recarga la página para ver el estado actual.',
    'error_server_prefix' => 'Error del servidor: ',

    // Frases de estado - generación de tokens
    'status_contacting_acme' => 'Contactando a Let\'s Encrypt...',
    'status_requesting_challenge' => 'Solicitando tokens de verificación...',
    'status_preparing_validation' => 'Preparando validación del dominio...',
    'status_generating_keys' => 'Generando llaves criptográficas...',
    'status_securing_channel' => 'Asegurando canal de comunicación...',
    'status_registering_domain' => 'Registrando tu dominio...',
    'status_almost_ready' => 'Casi listo...',

    // Frases de estado - verificación
    'status_still_checking' => 'Seguimos validando...',
    'status_not_stuck' => 'No está trabado, está esperando.',
    'status_tab_open' => 'Puedes dejar esta pestaña abierta y volver después.',
    'status_grab_coffee' => 'Buen momento para un café.',
    'status_tacos' => 'Técnicamente te da tiempo de ir por unos tacos.',
    'status_who_waits' => 'El que espera, encripta.',
    'status_spoiler' => 'Spoiler: va a funcionar.',
    'status_stare_slower' => 'Si miras fijamente la pantalla, tarda más. Es ciencia.',
    'status_polite_cert' => 'Tu certificado está formado en la fila. Es muy educado.',
    'status_deliberate' => 'No es lento, es minucioso.',
    'status_faith' => 'Todavía nada, pero seguimos con fe.',
    'status_future_you' => 'Tu yo del futuro con HTTPS te lo va a agradecer.',

    // Solo verificación DNS
    'status_dns_propagation' => 'El DNS puede tardar unos minutos en actualizarse.',
    'status_dns_vantage' => 'Let\'s Encrypt consulta tu DNS desde varios lugares del mundo. Todos tienen que coincidir.',
    'status_no_tracking' => 'Los registros TXT van de servidor en servidor. Sin número de guía.',
    'status_dns_rules' => 'Nos encantaría ir más rápido, pero el DNS manda.',
    'status_plot_twist' => 'Plot twist: esto es lo que tarda normalmente.',
    'status_dns_1983' => 'El DNS es de 1983. A veces se le nota.',

    // Solo verificación HTTP
    'status_http_fetching' => 'Let\'s Encrypt está buscando el archivo en tu servidor.',
    'status_http_vantage' => 'Let\'s Encrypt consulta tu servidor desde varios lugares del mundo.',

    // Dudas (después de 10 minutos): ayudan a encontrar el error
    'status_doubt_dns_saved' => 'Está tardando más de lo normal. Revisa que el registro TXT esté guardado en tu panel DNS.',
    'status_doubt_dns_name' => 'Algunos paneles agregan el dominio solos. Si pusiste el nombre completo, prueba solo con _acme-challenge.',
    'status_doubt_dns_value' => 'Revisa que el valor no tenga espacios ni comillas de más.',
    'status_doubt_dns_provider' => 'Si usas Cloudflare u otro DNS externo, el registro va ahí, no donde compraste el dominio.',
    'status_doubt_dns_recheck' => 'Todavía nada. Vale la pena darle otra revisada a tu panel DNS.',
    'status_doubt_dns_two_records' => 'Recuerda: son dos registros TXT con el mismo nombre.',
    'status_doubt_http_url' => 'Está tardando más de lo normal. Abre la URL del archivo en tu navegador y revisa que muestre el contenido.',
    'status_doubt_http_port' => 'El archivo tiene que responder por el puerto 80. Revisa que tu firewall no lo bloquee.',
    'status_doubt_http_extension' => 'Revisa que al nombre del archivo no se le haya agregado una extensión, como .txt.',
    'status_doubt_http_folder' => 'Todavía nada. Revisa que el archivo esté dentro de .well-known/acme-challenge.',
];
