<?php

// DRAFT translation: pending review by a native speaker.
return [
    'title' => 'System health',
    'last_check' => 'Last check: :time',
    'never' => 'No check has run yet',
    'alert_title' => 'Problem: :check',
    'test_title' => 'Test alert',
    'test_text' => 'Monitoring works (:time).',

    'checks' => [
        'disk' => 'Free disk',
        'memory' => 'Free memory',
        'horizon' => 'Background jobs (Horizon)',
        'failed_jobs' => 'Failed jobs (1 h)',
        'backlog' => 'Queued jobs',
        'backup' => 'Last backup',
        'ssl' => 'SSL certificate',
    ],

    'backup' => [
        'failed' => 'Backup failed',
        'cleanup_failed' => 'Backup cleanup failed',
        'unhealthy' => 'Backups are too old or too large',
    ],

    'restore' => [
        'failed_title' => 'Backup restore test failed',
    ],

    'value' => [
        'running' => 'running',
        'stopped' => 'stopped',
        'none' => 'none',
        'hours_ago' => ':hours h ago',
        'days' => ':days days',
    ],

    'detail' => [
        'disk' => ':free free of :total',
        'memory' => ':free free',
        'redis_down' => 'Redis does not answer',
        'failed_jobs' => 'see /horizon/failed',
    ],
];
