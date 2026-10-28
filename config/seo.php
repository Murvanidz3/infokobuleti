<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Indexing
    |--------------------------------------------------------------------------
    |
    | Production only: robots.txt lets search engines in when this is true.
    | Every other environment always answers "Disallow: /".
    |
    */

    'indexable' => (bool) env('SEO_INDEXABLE', true),

    /*
    |--------------------------------------------------------------------------
    | Full-page cache for visitors who are not signed in
    |--------------------------------------------------------------------------
    |
    | Hot public pages are kept as ready HTML for "ttl" seconds. Any change to a
    | listing or a setting starts a new cache version, so edits show at once.
    |
    */

    'page_cache' => [
        'enabled' => (bool) env('PAGE_CACHE', true),
        'ttl' => (int) env('PAGE_CACHE_TTL', 300),
        'routes' => [
            'home', 'hotels.index', 'hotels.show', 'real-estate.index', 'real-estate.show', 'classifieds.index',
            'classifieds.show', 'jobs.index', 'jobs.show', 'guide.index', 'guide.show', 'events.index',
            'events.show', 'news.index', 'news.show', 'business.show', 'pages.show',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Slow queries
    |--------------------------------------------------------------------------
    |
    | Queries slower than this (milliseconds) are written to the "slow-queries"
    | log with the page they came from. 0 switches the log off.
    |
    */

    'slow_query_ms' => (int) env('SLOW_QUERY_MS', 300),

];
