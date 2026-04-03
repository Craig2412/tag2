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
        'sandbox' => env('KIU_SANDBOX', true),
        'base_url' => env('KIU_BASE_URL'),
        'transport' => env('KIU_TRANSPORT', 'xml'),
        'timeout' => (int) env('KIU_TIMEOUT', 30),
        'verify' => env('KIU_VERIFY_SSL', true),
        'auth' => [
            'mode' => env('KIU_AUTH_MODE', 'none'),
            'username' => env('KIU_USERNAME'),
            'password' => env('KIU_PASSWORD'),
            'office_id' => env('KIU_OFFICE_ID'),
            'agent_sine' => env('KIU_AGENT_SINE'),
            'headers' => [
                'username' => env('KIU_HEADER_USERNAME', 'X-KIU-Username'),
                'password' => env('KIU_HEADER_PASSWORD', 'X-KIU-Password'),
                'office_id' => env('KIU_HEADER_OFFICE_ID', 'X-KIU-Office-Id'),
                'agent_sine' => env('KIU_HEADER_AGENT_SINE', 'X-KIU-Agent-Sine'),
            ],
        ],
        'default_headers' => [
            'Accept' => env('KIU_ACCEPT_HEADER', 'application/xml'),
            'User-Agent' => env('APP_NAME', 'Laravel').'/kiu-sandbox-client',
        ],
        'operations' => [
            'session' => [
                'path' => env('KIU_SESSION_PATH', '/session'),
                'transport' => env('KIU_SESSION_TRANSPORT', env('KIU_TRANSPORT', 'xml')),
                'content_type' => env('KIU_SESSION_CONTENT_TYPE', 'text/xml; charset=utf-8'),
                'soap_action' => env('KIU_SESSION_SOAP_ACTION'),
            ],
            'availability' => [
                'path' => env('KIU_AVAILABILITY_PATH', '/availability'),
                'transport' => env('KIU_AVAILABILITY_TRANSPORT', env('KIU_TRANSPORT', 'xml')),
                'content_type' => env('KIU_AVAILABILITY_CONTENT_TYPE', 'text/xml; charset=utf-8'),
                'soap_action' => env('KIU_AVAILABILITY_SOAP_ACTION'),
            ],
            'pricing' => [
                'path' => env('KIU_PRICING_PATH', '/pricing'),
                'transport' => env('KIU_PRICING_TRANSPORT', env('KIU_TRANSPORT', 'xml')),
                'content_type' => env('KIU_PRICING_CONTENT_TYPE', 'text/xml; charset=utf-8'),
                'soap_action' => env('KIU_PRICING_SOAP_ACTION'),
            ],
            'booking' => [
                'path' => env('KIU_BOOKING_PATH', '/booking'),
                'transport' => env('KIU_BOOKING_TRANSPORT', env('KIU_TRANSPORT', 'xml')),
                'content_type' => env('KIU_BOOKING_CONTENT_TYPE', 'text/xml; charset=utf-8'),
                'soap_action' => env('KIU_BOOKING_SOAP_ACTION'),
            ],
            'ticketing' => [
                'path' => env('KIU_TICKETING_PATH', '/ticketing'),
                'transport' => env('KIU_TICKETING_TRANSPORT', env('KIU_TRANSPORT', 'xml')),
                'content_type' => env('KIU_TICKETING_CONTENT_TYPE', 'text/xml; charset=utf-8'),
                'soap_action' => env('KIU_TICKETING_SOAP_ACTION'),
            ],
            'post_sale' => [
                'path' => env('KIU_POST_SALE_PATH', '/post-sale'),
                'transport' => env('KIU_POST_SALE_TRANSPORT', env('KIU_TRANSPORT', 'xml')),
                'content_type' => env('KIU_POST_SALE_CONTENT_TYPE', 'text/xml; charset=utf-8'),
                'soap_action' => env('KIU_POST_SALE_SOAP_ACTION'),
            ],
        ],
    ],

];
