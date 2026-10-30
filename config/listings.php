<?php

return [

    /*
    | Days a listing stays published before it expires, keyed by morph alias. Modules not listed
    | (places, events, articles) never expire. Becomes editable in the admin settings in Phase 5.
    */
    'expiry_days' => [
        'property' => 60,
        'project' => 90,
        'classified' => 30,
        'vacancy' => 30,
    ],

];
