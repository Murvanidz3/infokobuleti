<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS gateway
    |--------------------------------------------------------------------------
    |
    | "log" writes messages to the log (local and tests), "ubill" sends them
    | through UBill (Georgian provider, production), "twilio" is a fallback.
    | Sending is queued; every message is recorded in sms_messages with its
    | delivery status.
    |
    */

    'default' => env('SMS_DRIVER', 'log'),

    'sender' => env('SMS_SENDER', 'INFOKOBULETI'),

    /*
    | Show the code on screen while testing with the log driver. Ignored in
    | production and with any real driver.
    */
    'reveal_codes' => (bool) env('SMS_REVEAL_CODES', false),

    'queue' => env('SMS_QUEUE', 'default'),

    /*
    | SMS manager (admin): the largest campaign (recipients).
    */
    'manager' => [
        'max_recipients' => (int) env('SMS_MAX_RECIPIENTS', 20000),
    ],

    'drivers' => [

        'log' => [
            'channel' => env('SMS_LOG_CHANNEL'),
        ],

        /*
        | SMS_SENDER is the brand name approved in the UBill dashboard; its
        | numeric id is looked up by name unless UBILL_BRAND_ID is set. The
        | API app is restricted to the server's IP address. Delivery reports:
        | set the callback URL https://<site>/webhooks/sms/ubill and the
        | callback key (UBILL_CALLBACK_KEY) in the UBill API app settings.
        */
        'ubill' => [
            'api_key' => env('UBILL_API_KEY'),
            'brand_id' => env('UBILL_BRAND_ID'),
            'brand_name' => env('UBILL_BRAND_NAME', env('SMS_SENDER', 'INFOKOBULETI')),
            'callback_key' => env('UBILL_CALLBACK_KEY'),
            'base_url' => env('UBILL_BASE_URL', 'https://api.ubill.dev/v1/sms'),
            'min_balance' => (int) env('UBILL_MIN_BALANCE', 200),
        ],

        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_FROM', env('SMS_SENDER', 'INFOKOBULETI')),
            'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),
            'status_callback' => (bool) env('TWILIO_STATUS_CALLBACK', true),
        ],

    ],

];
