<?php

return [
    'title' => 'სისტემის მდგომარეობა',
    'last_check' => 'ბოლო შემოწმება: :time',
    'never' => 'შემოწმება ჯერ არ ჩატარებულა',
    'alert_title' => 'პრობლემა: :check',
    'test_title' => 'სატესტო შეტყობინება',
    'test_text' => 'მონიტორინგი მუშაობს (:time).',

    'checks' => [
        'disk' => 'თავისუფალი დისკი',
        'memory' => 'თავისუფალი მეხსიერება',
        'horizon' => 'ფონური დავალებები (Horizon)',
        'failed_jobs' => 'ჩავარდნილი დავალებები (1 სთ)',
        'backlog' => 'რიგში მყოფი დავალებები',
        'backup' => 'ბოლო backup',
        'ssl' => 'SSL სერტიფიკატი',
    ],

    'backup' => [
        'failed' => 'backup ვერ შეიქმნა',
        'cleanup_failed' => 'ძველი backup-ების წაშლა ვერ მოხერხდა',
        'unhealthy' => 'backup-ები მოძველებულია ან ზედმეტად დიდია',
    ],

    'restore' => [
        'failed_title' => 'backup-ის აღდგენის ტესტი ვერ შესრულდა',
    ],

    'value' => [
        'running' => 'მუშაობს',
        'stopped' => 'გაჩერებულია',
        'none' => 'არ არის',
        'hours_ago' => ':hours სთ წინ',
        'days' => ':days დღე',
    ],

    'detail' => [
        'disk' => ':free თავისუფალია :total-დან',
        'memory' => ':free თავისუფალია',
        'redis_down' => 'Redis არ პასუხობს',
        'failed_jobs' => 'იხილეთ /horizon/failed',
    ],
];
