<?php

// DRAFT translation: pending review by a native speaker.
return [
    'formats' => [
        'date' => 'D MMMM YYYY',
        'day_month' => 'D MMMM',
        'weekday_date' => ':weekday, :date',
    ],

    // Indexed like Carbon::dayOfWeek: 0 = Sunday.
    'weekdays' => ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],

    'today' => 'Сегодня',
    'yesterday' => 'Вчера',
    'minutes_ago' => ':count мин назад',
    'hours_ago' => ':count ч назад',
    'days_ago' => ':count день назад|:count дня назад|:count дней назад',

    // Nominative month names and short forms, indexed 1-12.
    'months' => [1 => 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
    'months_short' => [1 => 'янв', 'фев', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'],

    // Calendar column headings, Monday first.
    'weekdays_short' => ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
];
