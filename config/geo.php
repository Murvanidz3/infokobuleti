<?php

return [
    /*
    | Address search on the add-listing map. "nominatim" (OpenStreetMap, free, max 1 request per
    | second) or "fake" (fixed addresses, for local work and tests).
    */
    'geocoder' => [
        'driver' => env('GEOCODER_DRIVER', 'nominatim'),
        'url' => env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org/search'),
        'user_agent' => env('NOMINATIM_USER_AGENT', 'infokobuleti.com (info@infokobuleti.com)'),
        // west, north, east, south: Kobuleti municipality.
        'viewbox' => [41.62, 41.95, 42.15, 41.62],
    ],

    /*
    | Black Sea shoreline from Chakvi (south) to Pichvnari (north), as [lat, lng] points.
    | "Distance to the sea" is the distance to the nearest segment. Approximate: adjust the points
    | if a known beachfront address shows more than ~50 m.
    */
    'coastline' => [
        [41.7050, 41.7200],
        [41.7238, 41.7268],
        [41.7450, 41.7380],
        [41.7662, 41.7488],
        [41.7850, 41.7600],
        [41.8000, 41.7680],
        [41.8180, 41.7710],
        [41.8320, 41.7738],
        [41.8470, 41.7755],
        [41.8650, 41.7750],
        [41.8868, 41.7730],
        [41.9100, 41.7720],
    ],

    /*
    | Beyond this distance the value is not shown (inland villages, mountains).
    */
    'max_sea_distance_m' => 20000,
];
