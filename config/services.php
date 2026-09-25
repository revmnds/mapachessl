<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'acme' => [
        // Use staging environment for testing (doesn't count against rate limits)
        // Set to false for production certificates
        'staging' => env('ACME_STAGING', true),
        // Queue workers running generations, and how many more requests may wait in line
        // for one to free up; beyond that users are asked to come back later
        'workers' => (int) env('QUEUE_WORKERS', 4),
        'max_queue' => (int) env('ACME_MAX_QUEUE', 50),
    ],

    // Operator alerts; disabled while token or chat id are empty
    'telegram' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        // Day boundaries and send time of the daily summary
        'timezone' => env('TELEGRAM_TIMEZONE', 'America/Chicago'),
    ],

];
