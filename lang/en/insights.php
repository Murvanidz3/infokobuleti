<?php

return [
    'price' => [
        'title' => 'Price check',
        'city' => 'Kobuleti',
        'verdict' => [
            'below' => ':percent% below market',
            'fair' => 'Market price',
            'above' => ':percent% above market',
        ],
        'text' => 'Price per m²: :value. Median of similar listings: :median (:count listings, :area).',
        'owner_text' => 'Your price per m²: :value. Median of similar listings: :median (:count listings, :area).',
        'range' => 'Most are :from – :to / m²',
        'median' => 'Median',
        'this' => 'This listing',
        'yours' => 'Your price',
    ],
    'business' => [
        'title' => 'Business analytics',
        'conversion' => 'Conversion: calls and messages per view',
        'conversion_short' => 'Conversion',
        'best_day' => 'Best day to bump (most views)',
        'best_day_unknown' => 'Not enough data yet for the best day to bump',
        'price' => 'Price vs market',
    ],
];
