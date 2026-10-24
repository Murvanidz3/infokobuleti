<?php

return [
    'formats' => [
        'date' => 'D MMMM, YYYY',
        'day_month' => 'D MMMM',
        'weekday_date' => ':weekday, :date',
    ],

    // Indexed like Carbon::dayOfWeek: 0 = Sunday.
    'weekdays' => ['კვირა', 'ორშაბათი', 'სამშაბათი', 'ოთხშაბათი', 'ხუთშაბათი', 'პარასკევი', 'შაბათი'],

    'today' => 'დღეს',
    'yesterday' => 'გუშინ',
    'minutes_ago' => ':count წთ წინ',
    'hours_ago' => ':count სთ წინ',
    'days_ago' => ':count დღის წინ',
];
