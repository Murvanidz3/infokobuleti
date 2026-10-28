<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Alerts
    |--------------------------------------------------------------------------
    |
    | Every alert is written to storage/logs/alerts.log. When a Telegram bot is
    | configured (services.telegram.bot_token) and a chat id is set here, alerts
    | also go to that Telegram chat or channel. The same alert repeats at most
    | once per "repeat_minutes".
    |
    */

    'telegram_chat_id' => env('MONITORING_TELEGRAM_CHAT_ID'),

    'repeat_minutes' => (int) env('MONITORING_REPEAT_MINUTES', 360),

    /*
    |--------------------------------------------------------------------------
    | Thresholds
    |--------------------------------------------------------------------------
    */

    'disk_free_percent' => (int) env('MONITORING_DISK_FREE_PERCENT', 15),

    'memory_free_percent' => (int) env('MONITORING_MEMORY_FREE_PERCENT', 8),

    'queue_backlog' => (int) env('MONITORING_QUEUE_BACKLOG', 500),

    'backup_max_age_hours' => (int) env('MONITORING_BACKUP_MAX_AGE_HOURS', 26),

    'ssl_min_days' => (int) env('MONITORING_SSL_MIN_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Restore test
    |--------------------------------------------------------------------------
    |
    | backup:restore-test loads the newest database backup into this scratch
    | database every week (never into the live one) and compares row counts.
    |
    */

    'restore_database' => env('MONITORING_RESTORE_DATABASE', 'infokobuleti_restore_test'),

];
