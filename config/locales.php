<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default locale
    |--------------------------------------------------------------------------
    |
    | The default locale is served without a URL prefix (/). Every other
    | supported locale is served under its own prefix (/en, /ru).
    |
    */

    'default' => 'ka',

    /*
    |--------------------------------------------------------------------------
    | Supported locales
    |--------------------------------------------------------------------------
    |
    | "code" is the label shown in the language switcher, "hreflang" is used
    | for alternate links and "preload" lists the font files (relative to the
    | project root) the layout preloads for that locale.
    |
    */

    'supported' => [
        'ka' => [
            'code' => 'KA',
            'hreflang' => 'ka',
            'og_locale' => 'ka_GE',
            'preload' => [
                'node_modules/@fontsource-variable/noto-sans-georgian/files/noto-sans-georgian-georgian-wght-normal.woff2',
                'node_modules/@fontsource/noto-serif-georgian/files/noto-serif-georgian-georgian-600-normal.woff2',
            ],
        ],
        'en' => [
            'code' => 'EN',
            'hreflang' => 'en',
            'og_locale' => 'en_US',
            'preload' => [
                'node_modules/@fontsource-variable/noto-sans-georgian/files/noto-sans-georgian-latin-wght-normal.woff2',
                'node_modules/@fontsource/noto-serif-georgian/files/noto-serif-georgian-latin-600-normal.woff2',
            ],
        ],
        'ru' => [
            'code' => 'RU',
            'hreflang' => 'ru',
            'og_locale' => 'ru_RU',
            'preload' => [
                'node_modules/@fontsource-variable/noto-sans/files/noto-sans-cyrillic-wght-normal.woff2',
                'node_modules/@fontsource/noto-serif/files/noto-serif-cyrillic-600-normal.woff2',
            ],
        ],
    ],

];
