<?php

return [
    'center' => '{1} :count new listing for “:name”|[2,*] :count new listings for “:name”',

    'hints' => [
        'no_email' => 'no email on the account',
        'no_push' => 'turn it on in notifications first',
        'no_telegram' => 'link it in notifications first',
    ],

    'mail' => [
        'subject' => '{1} :count new listing · :name|[2,*] :count new listings · :name',
        'line' => '{1} Your saved search “:name” has :count new listing:|[2,*] Your saved search “:name” has :count new listings:',
        'action' => 'See all',
        'manage' => 'Change how often and where you get alerts here: :url',
    ],

    'push' => [
        'title' => '{1} :count new listing|[2,*] :count new listings',
        'more' => 'and :count more',
        'title_settings' => 'Notifications on this device',
        'devices' => '{0} Not on yet|{1} On for :count device|[2,*] On for :count devices',
        'text' => 'New listings for your saved searches arrive as browser notifications, even with the site closed.',
        'unsupported' => 'This browser cannot receive notifications. On iPhone, add the site to the home screen first.',
        'enable' => 'Turn on',
        'disable' => 'Turn off on this device',
        'denied' => 'Notifications are blocked in the browser. Allow them in the site settings.',
        'enabled' => 'Notifications are on.',
        'disabled' => 'Notifications are off.',
    ],

    'telegram' => [
        'text' => 'Link Telegram to get new listings for your saved searches there.',
        'linked' => 'Linked',
        'not_linked' => 'Not linked',
        'link' => 'Link Telegram',
        'open' => 'Open in Telegram',
        'open_hint' => 'Press “Start” in Telegram. The link works for 30 minutes.',
        'unlink' => 'Unlink',
        'confirm_unlink' => 'Unlink Telegram?',
        'unlinked' => 'Telegram unlinked.',
        'unavailable' => 'The Telegram bot is not switched on yet.',
    ],
];
