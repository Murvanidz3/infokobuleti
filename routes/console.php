<?php

use App\Models\ListingDraft;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Useful-info widgets: weather every half hour, NBG rates (published once a day) hourly.
Schedule::command('info:refresh --only=weather')->everyThirtyMinutes()->withoutOverlapping()->runInBackground();
Schedule::command('info:refresh --only=rates')->hourlyAt(7)->withoutOverlapping()->runInBackground();

// Listings past their expiry date become "expired"; owners are reminded 3 days before.
Schedule::command('listings:expire')->dailyAt('09:05')->withoutOverlapping()->onOneServer();

// Add-listing drafts nobody touched for 30 days (and their photos).
Schedule::command('model:prune', ['--model' => [ListingDraft::class]])->dailyAt('04:20')->onOneServer();
