<?php

use App\Http\Controllers\Account\AccountSecurityController;
use App\Http\Controllers\Account\OnboardingController;
use App\Http\Controllers\Account\VerifyPhoneController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PhoneLoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Staff\RecoveryCodesController;
use App\Http\Controllers\Staff\StaffLoginController;
use App\Http\Controllers\Staff\TwoFactorChallengeController;
use App\Http\Controllers\Staff\TwoFactorSetupController;
use App\Http\Controllers\StyleguideController;
use App\Http\Controllers\Webhooks\TwilioStatusController;
use App\Services\LocaleRouter;
use Illuminate\Support\Facades\Route;

// Not localized: the OAuth callback must match the URI registered with Google; webhooks are machine-to-machine.
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
Route::post('/webhooks/sms/twilio', TwilioStatusController::class)->name('webhooks.sms.twilio');

app(LocaleRouter::class)->group(function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/_styleguide', StyleguideController::class)->name('styleguide');

    Route::middleware('guest')->group(function (): void {
        Route::get('/login', LoginController::class)->name('login');
        Route::post('/auth/phone/send', [PhoneLoginController::class, 'send'])->middleware('throttle:otp')->name('auth.phone.send');
        Route::post('/auth/phone/verify', [PhoneLoginController::class, 'verify'])->middleware('throttle:otp')->name('auth.phone.verify');

        Route::get('/staff/login', [StaffLoginController::class, 'create'])->name('staff.login');
        Route::post('/staff/login', [StaffLoginController::class, 'store'])->name('staff.login.store');
        Route::get('/staff/two-factor', [TwoFactorChallengeController::class, 'create'])->name('staff.two-factor');
        Route::post('/staff/two-factor', [TwoFactorChallengeController::class, 'store'])->name('staff.two-factor.store');
        Route::get('/staff/two-factor/setup', [TwoFactorSetupController::class, 'create'])->name('staff.two-factor.setup');
        Route::post('/staff/two-factor/setup', [TwoFactorSetupController::class, 'store'])->name('staff.two-factor.setup.store');
    });

    // Guests sign in with Google here; signed-in users link Google to their account (?intent=link).
    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');

    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', LogoutController::class)->name('logout');
        Route::get('/onboarding', OnboardingController::class)->name('onboarding');
        Route::get('/staff/recovery-codes', RecoveryCodesController::class)->middleware('staff')->name('staff.recovery-codes');

        Route::middleware('onboarded')->group(function (): void {
            Route::get('/account', fn () => redirect()->to(localized_route('account.security')))->name('account');
            Route::get('/account/security', AccountSecurityController::class)->name('account.security');
            Route::get('/account/phone', VerifyPhoneController::class)->name('account.phone');
        });
    });
});
