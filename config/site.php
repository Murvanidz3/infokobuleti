<?php

return [

    /*
    | Main sections in header, drawer and footer order: lang key in nav.php => route name.
    */
    'nav' => [
        'hotels' => 'hotels.index',
        'real_estate' => 'real-estate.index',
        'classifieds' => 'classifieds.index',
        'jobs' => 'jobs.index',
        'guide' => 'guide.index',
        'news' => 'news.index',
    ],

    /*
    | Footer "information" links: lang key in footer.php => [route name, parameters].
    */
    'info_links' => [
        'about' => ['pages.show', ['page' => 'about']],
        'advertising' => ['contact', ['topic' => 'advertising']],
        'terms' => ['pages.show', ['page' => 'terms']],
        'privacy' => ['pages.show', ['page' => 'privacy']],
        'help' => ['contact', []],
    ],

    'contact' => [
        'phone' => '+995 422 29 00 00',
        'email' => 'contact@infokobuleti.com',
    ],

    'social' => [
        'facebook' => '#',
        'instagram' => '#',
        'youtube' => '#',
        'telegram' => '#',
    ],

    'apps' => [
        'app_store' => '#',
        'google_play' => '#',
    ],

];
