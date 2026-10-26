<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Card payments
    |--------------------------------------------------------------------------
    |
    | "gateways" lists the drivers offered at checkout, in order. "fake" is a
    | built-in test bank for local and staging: it never moves money and is
    | refused in production. "bog" (Bank of Georgia) and "tbc" (TBC Bank)
    | need the merchant credentials from each bank.
    |
    */

    'gateways' => array_values(array_filter(array_map('trim', explode(',', (string) env('PAYMENT_GATEWAYS', 'fake'))))),

    'currency' => 'GEL',

    /* Unpaid card orders are given up (and re-checked with the bank) after this many minutes. */
    'pending_timeout_minutes' => (int) env('PAYMENT_PENDING_TIMEOUT', 30),

    /* Wallet top-up limits, in GEL. */
    'top_up' => [
        'min' => 5,
        'max' => 2000,
        'presets' => [10, 20, 50, 100],
    ],

    /* Homepage VIP rotation: the order of promoted items reshuffles every N minutes. */
    'rotation_minutes' => (int) env('PROMOTION_ROTATION_MINUTES', 10),

    /* Hours between automatic bumps. */
    'auto_bump_every_hours' => 24,

    'invoice' => [
        'prefix' => env('INVOICE_PREFIX', 'IK'),
        'seller' => [
            'name' => env('INVOICE_SELLER_NAME', 'infokobuleti.com'),
            'tax_id' => env('INVOICE_SELLER_TAX_ID'),
            'address' => env('INVOICE_SELLER_ADDRESS'),
            'email' => env('INVOICE_SELLER_EMAIL', 'info@infokobuleti.com'),
            'iban' => env('INVOICE_SELLER_IBAN'),
        ],
        /* Noto Sans + Noto Sans Georgian merged into one TrueType file (SIL OFL) for the PDF renderer. */
        'font' => resource_path('fonts/Invoice-Regular.ttf'),
        'font_bold' => resource_path('fonts/Invoice-Bold.ttf'),
    ],

    'drivers' => [

        'fake' => [
            /* Signs the fake bank's callbacks (HMAC-SHA256), like a real bank would. */
            'secret' => env('FAKE_PAYMENTS_SECRET', env('APP_KEY')),
        ],

        /* Bank of Georgia Payments API (api.bog.ge/docs/en/payments). */
        'bog' => [
            'client_id' => env('BOG_CLIENT_ID'),
            'client_secret' => env('BOG_CLIENT_SECRET'),
            'auth_url' => env('BOG_AUTH_URL', 'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token'),
            'api_url' => env('BOG_API_URL', 'https://api.bog.ge/payments/v1'),
            /* The bank's callback public key (PEM, from the "Callback" page of the docs). */
            'public_key' => env('BOG_CALLBACK_PUBLIC_KEY'),
            'payment_methods' => ['card', 'google_pay', 'apple_pay'],
            'ttl_minutes' => 30,
        ],

        /* TBC Bank Checkout / tpay API (developers.tbcbank.ge). */
        'tbc' => [
            'api_key' => env('TBC_API_KEY'),
            'client_id' => env('TBC_CLIENT_ID'),
            'client_secret' => env('TBC_CLIENT_SECRET'),
            'api_url' => env('TBC_API_URL', 'https://api.tbcbank.ge/v1'),
            /* Callbacks come only from these addresses (TBC documentation). Empty = do not check. */
            'callback_ips' => array_values(array_filter(explode(',', (string) env('TBC_CALLBACK_IPS', '193.104.20.44,193.104.20.45,185.52.80.44,185.52.80.45')))),
            'expiration_minutes' => 12,
        ],

    ],

];
