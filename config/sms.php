<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS gateway
    |--------------------------------------------------------------------------
    |
    | "log" writes messages to the log (local and tests), "twilio" sends them
    | through Twilio. The Georgian provider driver is added once its API docs
    | are available. Sending is queued; every message is recorded in
    | sms_messages with its delivery status.
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

    'drivers' => [

        'log' => [
            'channel' => env('SMS_LOG_CHANNEL'),
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
