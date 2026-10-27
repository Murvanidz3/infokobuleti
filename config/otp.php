<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS login codes
    |--------------------------------------------------------------------------
    |
    | Codes ("length" digits) are stored only as an HMAC, expire after "ttl"
    | seconds (five minutes) and allow
    | "max_attempts" guesses. A new code can be requested after "resend_after"
    | seconds. The limits below cap how many codes one number or one IP
    | address can request; "decay" is the window in seconds.
    |
    */

    'length' => 4,

    'ttl' => 300,

    'max_attempts' => 5,

    'resend_after' => 60,

    'limits' => [
        'per_phone_hour' => ['max' => 5, 'decay' => 3600],
        'per_phone_day' => ['max' => 10, 'decay' => 86400],
        'per_ip_hour' => ['max' => 20, 'decay' => 3600],
        'verify_per_ip' => ['max' => 30, 'decay' => 600],
    ],

];
