<?php

// DRAFT translation: pending review by a native speaker.
return [
    'formats' => [
        'date' => 'D MMMM YYYY',
        'day_month' => 'D MMMM',
        'weekday_date' => ':weekday, :date',
    ],

    // Indexed like Carbon::dayOfWeek: 0 = Sunday.
    'weekdays' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],

    'today' => 'Today',
    'yesterday' => 'Yesterday',
    'minutes_ago' => ':count min ago',
    'hours_ago' => ':count h ago',
    'days_ago' => ':count day ago|:count days ago',

    // Nominative month names and short forms, indexed 1-12.
    'months' => [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    'months_short' => [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],

    // Calendar column headings, Monday first.
    'weekdays_short' => ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'],
];
