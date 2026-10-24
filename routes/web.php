<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\StyleguideController;
use App\Services\LocaleRouter;
use Illuminate\Support\Facades\Route;

app(LocaleRouter::class)->group(function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/_styleguide', StyleguideController::class)->name('styleguide');
});
