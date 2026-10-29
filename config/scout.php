<?php

use App\Models\Article;
use App\Models\Classified;
use App\Models\Event;
use App\Models\Hotel;
use App\Models\Place;
use App\Models\Property;
use App\Models\Vacancy;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    |
    | This option controls the default search connection that gets used while
    | using Laravel Scout. This connection is used when syncing all models
    | to the search service. You should adjust this based on your needs.
    |
    | Supported: "algolia", "meilisearch", "typesense", "turbopuffer",
    |            "database", "collection", "null"
    |
    */

    // "pgsql" (App\Search\PostgresSearchEngine) needs nothing but the database; staging uses Meilisearch.
    'driver' => env('SCOUT_DRIVER', 'pgsql'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    |
    | Here you may specify a prefix that will be applied to all search index
    | names used by Scout. This prefix may be useful if you have multiple
    | "tenants" or applications sharing the same search infrastructure.
    |
    */

    'prefix' => env('SCOUT_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    |
    | This option allows you to control if the operations that sync your data
    | with your search engines are queued. When this is set to "true" then
    | all automatic data syncing will get queued for better performance.
    |
    */

    'queue' => env('SCOUT_QUEUE', false),

    /*
    |--------------------------------------------------------------------------
    | Database Transactions
    |--------------------------------------------------------------------------
    |
    | This configuration option determines if your data will only be synced
    | with your search indexes after every open database transaction has
    | been committed, thus preventing any discarded data from syncing.
    |
    */

    'after_commit' => false,

    /*
    |--------------------------------------------------------------------------
    | Chunk Sizes
    |--------------------------------------------------------------------------
    |
    | These options allow you to control the maximum chunk size when you are
    | mass importing data into the search engine. This allows you to fine
    | tune each of these chunk sizes based on the power of the servers.
    |
    */

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | This option allows to control whether to keep soft deleted records in
    | the search indexes. Maintaining soft deleted records can be useful
    | if your application still needs to search for the records later.
    |
    */

    'soft_delete' => false,

    /*
    |--------------------------------------------------------------------------
    | Identify User
    |--------------------------------------------------------------------------
    |
    | This option allows you to control whether to notify the search engine
    | of the user performing the search. This is sometimes useful if the
    | engine supports any analytics based on this application's users.
    |
    | Supported engines: "algolia"
    |
    */

    'identify' => env('SCOUT_IDENTIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Algolia Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Algolia settings. Algolia is a cloud hosted
    | search engine which works great with Scout out of the box. Just plug
    | in your application ID and admin API key to get started searching.
    |
    */

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
        'index-settings' => [
            // Meilisearch settings live under scout.meilisearch. Scout reads them from the active driver.
        ],
        'model-settings' => [
            // User::class => [
            //     'embedding' => [
            //         'embedder' => 'default',
            //         'dimensions' => 1536,
            //     ],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    |
    | Titles first (ranked higher), then descriptions; Georgian, English and Russian side by side.
    | Synonyms are pushed separately from the admin settings (php artisan search:sync).
    |
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            Hotel::class => [
                'searchableAttributes' => ['title_ka', 'title_en', 'title_ru', 'description_ka', 'description_en', 'description_ru'],
                'filterableAttributes' => ['status', 'type', 'stars', 'price', 'distance_to_sea_m', 'rating', '_geo'],
                'sortableAttributes' => ['published_at', 'promotion_level'],
            ],
            Property::class => [
                'searchableAttributes' => ['title_ka', 'title_en', 'title_ru', 'description_ka', 'description_en', 'description_ru'],
                'filterableAttributes' => ['status', 'deal', 'type', 'price', 'area_m2', 'rooms', 'floor', 'condition', 'building_status', 'distance_to_sea_m', 'location_id', 'amenities', '_geo'],
                'sortableAttributes' => ['published_at', 'promotion_level', 'price', 'area_m2', 'distance_to_sea_m'],
            ],
            Classified::class => [
                'searchableAttributes' => ['title_ka', 'title_en', 'title_ru', 'description_ka', 'description_en', 'description_ru'],
                'filterableAttributes' => ['status', 'category_id', 'price', '_geo'],
                'sortableAttributes' => ['published_at', 'promotion_level'],
            ],
            Vacancy::class => [
                'searchableAttributes' => ['title_ka', 'title_en', 'title_ru', 'description_ka', 'description_en', 'description_ru'],
                'filterableAttributes' => ['status', 'category_id', 'employment_type', 'salary_min', '_geo'],
                'sortableAttributes' => ['published_at', 'promotion_level'],
            ],
            Place::class => [
                'searchableAttributes' => ['title_ka', 'title_en', 'title_ru', 'description_ka', 'description_en', 'description_ru'],
                'filterableAttributes' => ['status', 'type', '_geo'],
                'sortableAttributes' => ['published_at', 'promotion_level'],
            ],
            Article::class => [
                'searchableAttributes' => ['title_ka', 'title_en', 'title_ru', 'excerpt_ka', 'excerpt_en', 'excerpt_ru'],
                'filterableAttributes' => ['status'],
                'sortableAttributes' => ['published_at', 'promotion_level'],
            ],
            Event::class => [
                'searchableAttributes' => ['title_ka', 'title_en', 'title_ru', 'description_ka', 'description_en', 'description_ru'],
                'filterableAttributes' => ['status', 'category_id', 'starts_at', '_geo'],
                'sortableAttributes' => ['published_at', 'promotion_level'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Typesense Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Typesense settings. Typesense is an open
    | source search engine using minimal configuration. Below, you will
    | state the host, key, and schema configuration for the instance.
    |
    */

    'typesense' => [
        'client-settings' => [
            'api_key' => env('TYPESENSE_API_KEY', 'xyz'),
            'nodes' => [
                [
                    'host' => env('TYPESENSE_HOST', 'localhost'),
                    'port' => env('TYPESENSE_PORT', '8108'),
                    'path' => env('TYPESENSE_PATH', ''),
                    'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
                ],
            ],
            'nearest_node' => [
                'host' => env('TYPESENSE_HOST', 'localhost'),
                'port' => env('TYPESENSE_PORT', '8108'),
                'path' => env('TYPESENSE_PATH', ''),
                'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
            ],
            'connection_timeout_seconds' => env('TYPESENSE_CONNECTION_TIMEOUT_SECONDS', 2),
            'healthcheck_interval_seconds' => env('TYPESENSE_HEALTHCHECK_INTERVAL_SECONDS', 30),
            'num_retries' => env('TYPESENSE_NUM_RETRIES', 3),
            'retry_interval_seconds' => env('TYPESENSE_RETRY_INTERVAL_SECONDS', 1),
        ],
        // 'max_total_results' => env('TYPESENSE_MAX_TOTAL_RESULTS', 1000),
        'model-settings' => [
            // User::class => [
            //     'collection-schema' => [
            //         'fields' => [
            //             [
            //                 'name' => 'id',
            //                 'type' => 'string',
            //             ],
            //             [
            //                 'name' => 'name',
            //                 'type' => 'string',
            //             ],
            //             [
            //                 'name' => 'created_at',
            //                 'type' => 'int64',
            //             ],
            //         ],
            //         'default_sorting_field' => 'created_at',
            //     ],
            //     'search-parameters' => [
            //         'query_by' => 'name'
            //     ],
            //     'embedding' => [
            //         'attribute' => 'embedding',
            //         'dimensions' => 1536,
            //     ],
            // ],
        ],
        'import_action' => env('TYPESENSE_IMPORT_ACTION', 'upsert'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Turbopuffer Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Turbopuffer connection and the schema and
    | searchable attributes defined by each of your application's models.
    | Turbopuffer is a scalable engine with full-text + vector search.
    |
    */

    'turbopuffer' => [
        'api_key' => env('TURBOPUFFER_API_KEY'),
        'region' => env('TURBOPUFFER_REGION', 'gcp-us-central1'),
        'base_url' => env('TURBOPUFFER_BASE_URL'),
        'timeout' => env('TURBOPUFFER_TIMEOUT', 60),
        'connect_timeout' => env('TURBOPUFFER_CONNECT_TIMEOUT', 5),
        'retries' => env('TURBOPUFFER_RETRIES', 3),
        'model-settings' => [
            // User::class => [
            //     'searchable-attributes' => [
            //         'name' => 2,
            //         'email' => 1,
            //     ],
            //     'embedding' => [
            //         'attribute' => 'embedding',
            //         'dimensions' => 1536,
            //     ],
            //     'schema' => [
            //         'name' => ['type' => 'string', 'full_text_search' => true],
            //         'email' => ['type' => 'string', 'full_text_search' => true],
            //         'embedding' => ['type' => '[1536]f32', 'ann' => true],
            //     ],
            // ],
        ],
    ],

];
