<?php

return [

    'default' => 'GEL',

    /*
    | Lari per one unit of each currency. Used by the fixed driver (local, tests) and as the fallback
    | when the National Bank of Georgia API is unreachable.
    */
    'fallback_rates' => [
        'USD' => (float) env('CURRENCY_RATE_USD', 2.71),
        'EUR' => (float) env('CURRENCY_RATE_EUR', 3.14),
    ],

];
