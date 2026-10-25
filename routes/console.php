<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Useful-info widgets: weather every half hour, NBG rates (published once a day) hourly.
Schedule::command('info:refresh --only=weather')->everyThirtyMinutes()->withoutOverlapping()->runInBackground();
Schedule::command('info:refresh --only=rates')->hourlyAt(7)->withoutOverlapping()->runInBackground();
