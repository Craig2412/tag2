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

    'kiu' => [
        // Endpoint base WS3
        'base_url' => env('KIU_BASE_URL', 'https://ssl00.kiusys.com/ws3/index.php'),

        // Entorno
        'sandbox' => env('KIU_SANDBOX', true),

        // Transporte y conexión
        'transport' => env('KIU_TRANSPORT', 'form_params'),
        'timeout' => (int) env('KIU_TIMEOUT', 30),
        'verify' => env('KIU_VERIFY_SSL', false),

        // KIU XML defaults
        'target' => env('KIU_TARGET', 'Production'),
        'version' => env('KIU_VERSION', '3.0'),
        'primary_lang' => env('KIU_PRIMARY_LANG', 'en-us'),
        'iso_country' => env('KIU_ISO_COUNTRY', 'PA'),
        'iso_currency' => env('KIU_ISO_CURRENCY', 'USD'),
        'requestor_type' => env('KIU_REQUESTOR_TYPE', '5'),
        'booking_channel_type' => env('KIU_BOOKING_CHANNEL_TYPE', '1'),
        'pseudo_city_code' => env('KIU_PSEUDO_CITY_CODE', ''),

        // Credenciales (van en el body como user/password/request)
        'auth' => [
            'username' => env('KIU_USERNAME'),
            'password' => env('KIU_PASSWORD'),
            'office_id' => env('KIU_OFFICE_ID'),
            'agent_sine' => env('KIU_AGENT_SINE'),
        ],

        // Headers HTTP
        'default_headers' => [
            'Accept' => 'application/xml, text/xml, */*',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ],

        // Todas las operaciones comparten el mismo endpoint y transporte
        'operations' => [
            'session'      => ['path' => ''],
            'availability' => ['path' => ''],
            'pricing'      => ['path' => ''],
            'booking'      => ['path' => ''],
            'ticketing'    => ['path' => ''],
            'post_sale'    => ['path' => ''],
        ],
    ],

];
