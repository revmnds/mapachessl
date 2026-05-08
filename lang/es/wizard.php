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
    'step1_wildcard_hint' => '(requiere DNS)',

    // Step 2: Email
    'step2_title' => 'Tu email',
    'step2_subtitle' => 'Para notificarte antes de que expire',
    'step2_placeholder' => 'tu@email.com',

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
    'no_refresh_warning' => 'No refresques la página',
    'no_refresh_hint' => 'o el proceso se reiniciará',

    // Aviso de DNS viejo
    'stale_dns_title' => 'Registros DNS antiguos detectados',
    'stale_dns_found' => 'Registros encontrados:',
    'stale_dns_retry' => 'Ya los borré, verificar de nuevo',

    // Step 5: Éxito
    'step5_success_title' => 'Listo',
    'step5_expires_label' => 'Expira',
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

    // Frases de estado - verificación (normales + graciosas)
    'status_still_checking' => 'Seguimos validando...',
    'status_patience_virtue' => 'La paciencia es una virtud...',
    'status_still_on_it' => 'Seguimos en ello...',
    'status_dns_takes_time' => 'El DNS puede tardar, no desesperes...',
    'status_keep_waiting' => 'Seguimos verificando...',
    'status_no_worries' => 'Tranquilo, esto es normal...',
    'status_doing_fine' => 'Todo bien, solo esperamos...',
    'status_still_here' => 'Aquí seguimos, trabajando...',
    'status_any_moment' => 'Podría ser en cualquier momento...',
    'status_propagation_slow' => 'La propagación puede tardar un poco...',
    'status_grab_coffee' => 'Buen momento para un café...',
    'status_not_stuck' => 'No está atorado, solo esperando...',
    'status_servers_thinking' => 'Los servidores están pensando...',
    'status_almost_there' => 'Ya mero...',
    'status_stretch_legs' => 'Mientras tanto, ¿ya estiraste las piernas?',
    'status_bits_rhythm' => 'Los bits viajan a su propio ritmo...',
    'status_take_a_breath' => 'Tómate un respiro, nosotros vigilamos...',
    'status_internet_magic' => 'El internet está haciendo su magia...',
    'status_watching_plants' => 'Esto es como ver crecer una planta, pero más rápido...',
    'status_relax' => 'Relájate, estamos en ello...',
    'status_coffee_good' => 'Un cafecito no le hace mal a nadie...',
    'status_tech_needs_time' => 'La tecnología también necesita su tiempo...',
    'status_breathe_deep' => 'Respira profundo, ya casi...',
    'status_closer_than_think' => 'Estamos más cerca de lo que crees...',
    'status_not_easy' => 'Si fuera fácil, no sería divertido...',
    'status_meditating' => 'No, no se trabó. Solo está meditando...',
    'status_internet_elves' => 'Los duendes de internet están trabajando...',
    'status_no_f5' => 'No hagas F5, que nos mareas...',
    'status_stare_slower' => 'Si miras fijamente la pantalla, tarda más. Es ciencia.',
    'status_not_mining' => 'Prometemos que no estamos minando bitcoin...',
    'status_reboot_patience' => '¿Ya probaste apagar y encender tu paciencia?',
    'status_dns_speed' => 'Verificando a la velocidad del DNS... que no es mucha...',
    'status_dns_fast_joke' => 'Si el DNS fuera rápido, no necesitarías este mensaje...',
    'status_convincing_servers' => 'Estamos convenciendo a los servidores DNS...',
    'status_hamsters' => 'Nuestros hamsters están pedaleando lo más rápido que pueden...',
    'status_plot_twist' => 'Plot twist: el DNS sí tarda así de normal...',
    'status_spoiler' => 'Spoiler: va a funcionar. Solo dale tiempo...',
    'status_not_a_bug' => 'Esto no es un bug, es una feature de la paciencia...',
    'status_dns_meaning' => 'Mientras esperas, ¿sabías que DNS significa Domain Name System?',
    'status_reggaeton' => 'Dato random: un certificado SSL tiene más líneas que una canción de reggaetón...',
    'status_tacos' => 'Técnicamente podrías ir por tacos y volver...',
    'status_optimism' => 'Aún nada, pero el optimismo es gratis...',
    'status_mindfulness' => 'Piensa en esto como un ejercicio de mindfulness...',
    'status_waiting_room' => 'Estamos en la sala de espera del internet...',
    'status_no_tracking' => 'Los paquetes DNS están en camino. Sin tracking, eso sí...',
    'status_inhale_security' => 'Respira. Inhala seguridad, exhala HTTP...',
    'status_good_things_take' => 'Dicen que lo bueno tarda. Tu certificado será buenísimo...',
    'status_billions' => '¿Sabías que Let\'s Encrypt ha emitido miles de millones de certificados?',
    'status_polite_cert' => 'Tu certificado está en la fila. Es educado y espera su turno...',
    'status_dns_rules' => 'Nos encantaría ir más rápido, pero los DNS mandan...',
    'status_who_waits' => 'El que espera, encripta...',
    'status_like_chrome' => 'Procesando... como tu computadora cuando abres Chrome...',
    'status_all_night' => 'Tenemos toda la noche. Bueno, 30 minutos...',
    'status_deliberate' => 'No es lento, es... deliberado...',
    'status_thorough' => 'Let\'s Encrypt está verificando. Son muy minuciosos...',
    'status_https_1994' => 'Fun fact: HTTPS fue inventado por Netscape en 1994...',
    'status_each_second' => 'Recuerda: cada segundo de espera es un segundo más de seguridad...',
    'status_electrons' => 'Los electrones están dando la vuelta al mundo para verificar tu dominio...',
    'status_faith' => 'Todavía nada, pero no hemos perdido la fe...',
    'status_future_you' => 'Tu futuro yo con HTTPS te lo va a agradecer...',

    // Frases de duda (aparecen después de 10 minutos)
    'status_doubt_check_dns' => '¿Seguro que configuraste bien los DNS?',
    'status_doubt_recheck' => '¿Y si revisas nuevamente, por si acaso?',
    'status_doubt_two_records' => 'Oye... ¿sí agregaste los dos registros TXT?',
    'status_doubt_spaces' => '¿Los copiaste bien? A veces un espacio de más...',
    'status_doubt_panel' => 'Quizá vale la pena verificar en tu panel DNS...',
    'status_doubt_together' => 'Todavía nada... ¿revisamos juntos?',
    'status_doubt_longer' => 'Hmm, está tardando más de lo normal...',
    'status_doubt_ttl' => '¿Será que el TTL de tu DNS es muy alto?',
    'status_doubt_configured' => 'Sin presión, pero... ¿ya los configuraste?',
    'status_doubt_provider' => 'A lo mejor tu proveedor DNS está dormido...',
    'status_doubt_glance' => 'Solo digo... un vistazo rápido a tu panel DNS no estaría de más...',
    'status_doubt_a_while' => 'No es por molestar, pero llevamos un rato...',
    'status_doubt_correct_domain' => '¿Configuraste los registros en el dominio correcto?',
    'status_doubt_trailing_space' => 'Revisa que no haya espacios al inicio o final del valor TXT...',
    'status_doubt_slow_provider' => '¿Tu proveedor DNS es de los lentos? Pregunta honesta...',
];
