<?php

// DRAFT translation: pending review by a native speaker.
return [
    'title' => 'Состояние системы',
    'last_check' => 'Последняя проверка: :time',
    'never' => 'Проверок ещё не было',
    'alert_title' => 'Проблема: :check',
    'test_title' => 'Тестовое уведомление',
    'test_text' => 'Мониторинг работает (:time).',

    'checks' => [
        'disk' => 'Свободный диск',
        'memory' => 'Свободная память',
        'horizon' => 'Фоновые задачи (Horizon)',
        'failed_jobs' => 'Упавшие задачи (1 ч)',
        'backlog' => 'Задачи в очереди',
        'backup' => 'Последний бэкап',
        'ssl' => 'SSL-сертификат',
        'sms' => 'Баланс SMS (UBill)',
    ],

    'backup' => [
        'failed' => 'Бэкап не создан',
        'cleanup_failed' => 'Очистка старых бэкапов не удалась',
        'unhealthy' => 'Бэкапы устарели или слишком велики',
    ],

    'restore' => [
        'failed_title' => 'Тест восстановления бэкапа не прошёл',
    ],

    'value' => [
        'running' => 'работает',
        'stopped' => 'остановлен',
        'none' => 'нет',
        'hours_ago' => ':hours ч назад',
        'days' => ':days дн.',
        'sms' => ':count SMS',
    ],

    'detail' => [
        'disk' => 'свободно :free из :total',
        'memory' => 'свободно :free',
        'redis_down' => 'Redis не отвечает',
        'failed_jobs' => 'см. /horizon/failed',
    ],
];
