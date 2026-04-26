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
        // Endpoint base SOAP (ws3)
        'base_url' => env('KIU_BASE_URL', 'https://ssl00.kiusys.com/ws3/index.php'),

        // Entorno
        'sandbox' => env('KIU_SANDBOX', true),

        // Transporte por defecto de integracion legacy
        'transport' => env('KIU_TRANSPORT', 'form_params'),
        'target' => env('KIU_TARGET', 'Production'),
        'version' => env('KIU_VERSION', '3.0'),
        'primary_lang' => env('KIU_PRIMARY_LANG', 'en-us'),
        'iso_country' => env('KIU_ISO_COUNTRY', 'PA'),
        'iso_currency' => env('KIU_ISO_CURRENCY', 'USD'),
        'requestor_type' => env('KIU_REQUESTOR_TYPE', '5'),
        'booking_channel_type' => env('KIU_BOOKING_CHANNEL_TYPE', '1'),

        // Credenciales por defecto
        'auth' => [
            'username' => env('KIU_USERNAME'),
            'password' => env('KIU_PASSWORD'),
            'office_id' => env('KIU_OFFICE_ID'),
            'agent_sine' => env('KIU_AGENT_SINE'),
        ],

        // Headers por defecto
        'default_headers' => [
            'Accept' => env('KIU_ACCEPT_HEADER', 'application/xml, text/xml, */*'),
            'Content-Type' => env('KIU_CONTENT_TYPE', 'application/x-www-form-urlencoded'),
        ],

        // Timeout y SSL
        'timeout' => (int) env('KIU_TIMEOUT', 30),
        'verify' => env('KIU_VERIFY_SSL', true),

        // Configuracion de operaciones legacy
        'operations' => [
            'session' => [
                'path' => env('KIU_SESSION_PATH', '/'),
                'content_type' => 'application/x-www-form-urlencoded',
                'soap_action' => env('KIU_SESSION_SOAP_ACTION', ''),
                'transport' => env('KIU_SESSION_TRANSPORT', 'form_params'),
            ],
            'availability' => [
                'path' => env('KIU_AVAILABILITY_PATH', '/'),
                'content_type' => 'application/x-www-form-urlencoded',
                'soap_action' => env('KIU_AVAILABILITY_SOAP_ACTION', ''),
                'transport' => env('KIU_AVAILABILITY_TRANSPORT', 'form_params'),
            ],
            'pricing' => [
                'path' => env('KIU_PRICING_PATH', '/'),
                'content_type' => 'application/x-www-form-urlencoded',
                'soap_action' => env('KIU_PRICING_SOAP_ACTION', ''),
                'transport' => env('KIU_PRICING_TRANSPORT', 'form_params'),
            ],
            'booking' => [
                'path' => env('KIU_BOOKING_PATH', '/'),
                'content_type' => 'application/x-www-form-urlencoded',
                'soap_action' => env('KIU_BOOKING_SOAP_ACTION', ''),
                'transport' => env('KIU_BOOKING_TRANSPORT', 'form_params'),
            ],
            'ticketing' => [
                'path' => env('KIU_TICKETING_PATH', '/'),
                'content_type' => 'application/x-www-form-urlencoded',
                'soap_action' => env('KIU_TICKETING_SOAP_ACTION', ''),
                'transport' => env('KIU_TICKETING_TRANSPORT', 'form_params'),
            ],
            'post_sale' => [
                'path' => env('KIU_POST_SALE_PATH', '/'),
                'content_type' => 'application/x-www-form-urlencoded',
                'soap_action' => env('KIU_POST_SALE_SOAP_ACTION', ''),
                'transport' => env('KIU_POST_SALE_TRANSPORT', 'form_params'),
            ],
        ],
    ],

];
