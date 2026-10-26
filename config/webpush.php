<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Web push (Phase 9)
    |--------------------------------------------------------------------------
    |
    | VAPID keys identify this site to the browsers' push services. Create them
    | once with "php artisan webpush:keys" and keep them: changing them drops
    | every existing subscription.
    |
    */

    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:contact@infokobuleti.com'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

];
