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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'otp' => [
        'driver' => env('OTP_DRIVER', 'local'),
        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'service_sid' => env('TWILIO_VERIFY_SERVICE_SID'),
        ],
    ],

    'whatsapp' => [
        'version' => env('META_GRAPH_VERSION', env('WHATSAPP_GRAPH_VERSION', 'v26.0')),
        'token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'verify_token' => env('META_WEBHOOK_VERIFY_TOKEN', env('WHATSAPP_VERIFY_TOKEN')),
        'app_secret' => env('META_APP_SECRET', env('WHATSAPP_APP_SECRET')),
    ],

    'meta_whatsapp' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'config_id' => env('META_CONFIG_ID'),
        'graph_version' => env('META_GRAPH_VERSION', 'v26.0'),
        'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN', env('WHATSAPP_VERIFY_TOKEN')),
    ],

];
