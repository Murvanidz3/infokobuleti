<?php

/*
| The "useful info" widgets (App\Services\Info\UsefulInfo), refreshed by php artisan info:refresh.
*/
return [

    'weather' => [
        'forecast_url' => env('OPEN_METEO_URL', 'https://api.open-meteo.com/v1/forecast'),
        'marine_url' => env('OPEN_METEO_MARINE_URL', 'https://marine-api.open-meteo.com/v1/marine'),
        // Kobuleti centre, and a point just off the beach for the sea temperature.
        'lat' => 41.8214,
        'lng' => 41.7792,
        'sea_lat' => 41.8214,
        'sea_lng' => 41.7550,
    ],

    'rates' => [
        'url' => env('NBG_RATES_URL', 'https://nbg.gov.ge/gw/api/ct/monetarypolicy/currencies/en/json/'),
        'currencies' => ['USD', 'EUR', 'TRY'],
    ],

    // Defaults until an admin edits them under Settings → Useful info.
    'transport' => [
        'airport_km' => 30,
        'airport_minutes' => 35,
    ],

];
