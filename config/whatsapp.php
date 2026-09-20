<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Notifications Enabled
    |--------------------------------------------------------------------------
    |
    | Set to false to disable sending any WhatsApp messages.
    |
    */
    'enabled' => env('WHATSAPP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Gateway Provider
    |--------------------------------------------------------------------------
    |
    | Supported providers: 'meta' (Meta WhatsApp Cloud API), 'fonnte', 'log'
    |
    */
    'provider' => env('WHATSAPP_PROVIDER', 'meta'),

    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Cloud API Configuration
    |--------------------------------------------------------------------------
    */
    'meta' => [
        'version' => env('WHATSAPP_API_VERSION', 'v22.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', '1272145405984391'),
        'token' => env('WHATSAPP_API_TOKEN', ''),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional: Fonnte Configuration (Alternative gateway)
    |--------------------------------------------------------------------------
    */
    'fonnte' => [
        'token' => env('FONNTE_TOKEN', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Base URL for Tracking Links
    |--------------------------------------------------------------------------
    */
    'app_url' => env('APP_URL', 'http://localhost:8000'),
];
