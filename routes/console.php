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

// Promotions: auto-bumps, VIP / package expiry and "ends tomorrow" reminders.
Schedule::command('promotions:run')->everyTenMinutes()->withoutOverlapping()->onOneServer();

// Card payments whose callback never came are re-checked with the bank; abandoned orders close.
Schedule::command('payments:reconcile')->everyFifteenMinutes()->withoutOverlapping()->onOneServer();

// Saved-search alerts: instant ones every run, daily and weekly ones from 09:00.
Schedule::command('alerts:run')->everyTenMinutes()->withoutOverlapping()->onOneServer();

// XML sitemaps for search engines (one file per module).
Schedule::command('sitemap:generate')->dailyAt('03:40')->withoutOverlapping()->onOneServer();

// Expired account-data exports are removed after their download link stops working.
Schedule::command('privacy:prune-exports')->dailyAt('04:40')->onOneServer();

// Server and application health every five minutes; problems go to the alert channels.
Schedule::command('monitor:check')->everyFiveMinutes()->withoutOverlapping()->onOneServer()->runInBackground();

// Backups: database + uploads every night onto the backups disk; old ones are thinned out first.
Schedule::command('backup:clean')->dailyAt('01:30')->withoutOverlapping()->onOneServer();
Schedule::command('backup:run')->dailyAt('02:00')->withoutOverlapping()->onOneServer();
Schedule::command('backup:monitor')->dailyAt('06:00')->onOneServer();

// Every Sunday the newest production backup is restored into a scratch database and checked.
Schedule::command('backup:restore-test')->weeklyOn(0, '05:00')->environments(['production'])->withoutOverlapping()->onOneServer();

// SMS manager: scheduled campaigns start on time; delivery reports that never came are fetched.
Schedule::command('sms:send-scheduled')->everyMinute()->withoutOverlapping()->onOneServer();
Schedule::command('sms:sync-reports')->everyTenMinutes()->withoutOverlapping()->onOneServer()->runInBackground();
