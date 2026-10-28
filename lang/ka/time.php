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

    // Nominative month names and short forms, indexed 1-12.
    'months' => [1 => 'იანვარი', 'თებერვალი', 'მარტი', 'აპრილი', 'მაისი', 'ივნისი', 'ივლისი', 'აგვისტო', 'სექტემბერი', 'ოქტომბერი', 'ნოემბერი', 'დეკემბერი'],
    'months_short' => [1 => 'იან', 'თებ', 'მარ', 'აპრ', 'მაი', 'ივნ', 'ივლ', 'აგვ', 'სექ', 'ოქტ', 'ნოე', 'დეკ'],

    // Calendar column headings, Monday first.
    'weekdays_short' => ['ორ', 'სა', 'ოთ', 'ხუ', 'პა', 'შა', 'კვ'],
];
