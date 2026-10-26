<?php

use App\Http\Controllers\Account\AccountSecurityController;
use App\Http\Controllers\Account\ApplicationCvController;
use App\Http\Controllers\Account\ConversationController;
use App\Http\Controllers\Account\HotelRoomsController;
use App\Http\Controllers\Account\ListingWizardController;
use App\Http\Controllers\Account\OnboardingController;
use App\Http\Controllers\Account\VerifyPhoneController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PhoneLoginController;
use App\Http\Controllers\Billing\FakeBankController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PaymentReturnController;
use App\Http\Controllers\Billing\PromoteListingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Site\BookingRequestController;
use App\Http\Controllers\Site\BusinessProfileController;
use App\Http\Controllers\Site\ClassifiedController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\EventController;
use App\Http\Controllers\Site\FavoriteController;
use App\Http\Controllers\Site\GuideController;
use App\Http\Controllers\Site\HotelController;
use App\Http\Controllers\Site\MessageController;
use App\Http\Controllers\Site\NewsController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\PhoneRevealController;
use App\Http\Controllers\Site\PropertyController;
use App\Http\Controllers\Site\ReportController;
use App\Http\Controllers\Site\SearchController;
use App\Http\Controllers\Site\VacancyApplicationController;
use App\Http\Controllers\Site\VacancyController;
use App\Http\Controllers\Staff\RecoveryCodesController;
use App\Http\Controllers\Staff\StaffLoginController;
use App\Http\Controllers\Staff\TwoFactorChallengeController;
use App\Http\Controllers\Staff\TwoFactorSetupController;
use App\Http\Controllers\StyleguideController;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use App\Http\Controllers\Webhooks\TwilioStatusController;
use App\Services\Account\OwnerListings;
use App\Services\LocaleRouter;
use App\Support\Listings\ListingTypes;
use Illuminate\Support\Facades\Route;

// Not localized: the OAuth callback must match the URI registered with Google; webhooks are machine-to-machine.
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
Route::post('/webhooks/sms/twilio', TwilioStatusController::class)->name('webhooks.sms.twilio');
Route::post('/webhooks/payments/{gateway}', PaymentWebhookController::class)->whereAlpha('gateway')->middleware('throttle:120,1')->name('webhooks.payments');

// Staff support: browse the site as a user (started from the admin), and come back.
Route::middleware('auth')->group(function (): void {
    Route::post('/admin/impersonate/{user}', [ImpersonationController::class, 'start'])->middleware('staff')->name('impersonation.start');
    Route::post('/impersonation/leave', [ImpersonationController::class, 'leave'])->name('impersonation.leave');
});

app(LocaleRouter::class)->group(function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/_styleguide', StyleguideController::class)->name('styleguide');

    // Module list pages (Livewire filters inside) and detail pages.
    Route::view('/hotels', 'pages.hotels.index')->name('hotels.index');
    Route::get('/hotels/{hotel}', [HotelController::class, 'show'])->name('hotels.show');
    Route::post('/hotels/{hotel}/booking', BookingRequestController::class)->middleware('throttle:interactions')->name('hotels.booking');

    Route::view('/real-estate', 'pages.real-estate.index')->name('real-estate.index');
    Route::get('/real-estate/{property}', [PropertyController::class, 'show'])->name('real-estate.show');

    Route::view('/classifieds', 'pages.classifieds.index')->name('classifieds.index');
    Route::get('/classifieds/{classified}', [ClassifiedController::class, 'show'])->name('classifieds.show');

    Route::view('/jobs', 'pages.jobs.index')->name('jobs.index');
    Route::get('/jobs/{vacancy}', [VacancyController::class, 'show'])->name('jobs.show');
    Route::post('/jobs/{vacancy}/apply', VacancyApplicationController::class)->middleware(['auth', 'onboarded', 'throttle:interactions'])->name('jobs.apply');

    Route::get('/business/{businessProfile}', BusinessProfileController::class)->name('business.show');

    Route::get('/guide', [GuideController::class, 'index'])->name('guide.index');
    Route::get('/guide/{place}', [GuideController::class, 'show'])->name('guide.show');
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::get('/news', [NewsController::class, 'index'])->name('news.index');
    Route::get('/news/{article}', [NewsController::class, 'show'])->name('news.show');

    // The fake test bank (staging): signed links, refused when the fake gateway is switched off.
    Route::get('/payments/fake/{order}', [FakeBankController::class, 'show'])->middleware('signed')->name('payments.fake.show');
    Route::post('/payments/fake/{order}', [FakeBankController::class, 'decide'])->middleware(['signed', 'throttle:interactions'])->name('payments.fake.decide');

    Route::get('/search', [SearchController::class, 'index'])->middleware('throttle:search')->name('search');
    Route::get('/search/suggest', [SearchController::class, 'suggest'])->middleware('throttle:search')->name('search.suggest');

    Route::get('/info/{page:slug}', PageController::class)->name('pages.show');
    Route::get('/contact', [ContactController::class, 'create'])->name('contact');
    Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');

    // Interactions with any public item ({type} is its morph alias).
    $types = ListingTypes::INTERACTIVE;
    Route::post('/favorites/{type}/{id}', FavoriteController::class)->whereIn('type', $types)->whereNumber('id')->middleware('throttle:interactions')->name('favorites.toggle');
    Route::post('/phone/{type}/{id}', PhoneRevealController::class)->whereIn('type', $types)->whereNumber('id')->middleware('throttle:phone-reveal')->name('phone.reveal');
    Route::post('/report/{type}/{id}', ReportController::class)->whereIn('type', $types)->whereNumber('id')->middleware(['auth', 'throttle:interactions'])->name('report.store');
    Route::post('/message/{type}/{id}', MessageController::class)->whereIn('type', $types)->whereNumber('id')->middleware(['auth', 'onboarded', 'throttle:interactions'])->name('message.store');

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
            // Account area (Phase 7).
            Route::view('/account', 'pages.account.dashboard')->name('account');
            Route::view('/account/listings', 'pages.account.listings')->name('account.listings');
            Route::view('/account/favorites', 'pages.account.favorites')->name('account.favorites');
            Route::view('/account/searches', 'pages.account.searches')->name('account.searches');
            Route::view('/account/notifications', 'pages.account.notifications')->name('account.notifications');
            Route::view('/account/profile', 'pages.account.profile')->name('account.profile');
            Route::view('/account/billing', 'pages.account.billing')->name('account.billing');
            Route::get('/account/billing/invoices/{order}', InvoiceController::class)->whereNumber('order')->name('account.invoice');
            Route::view('/account/analytics', 'pages.account.analytics')->name('account.analytics');
            Route::get('/account/promote/{type}/{id}', PromoteListingController::class)
                ->whereIn('type', array_keys(OwnerListings::TYPES))->whereNumber('id')->name('account.promote');
            Route::get('/payments/{payment}/return', PaymentReturnController::class)->whereNumber('payment')->name('payments.return');
            Route::get('/account/security', AccountSecurityController::class)->name('account.security');
            Route::get('/account/phone', VerifyPhoneController::class)->name('account.phone');

            Route::get('/account/messages', [ConversationController::class, 'index'])->name('account.messages');
            Route::get('/account/messages/{conversation}', [ConversationController::class, 'show'])->whereNumber('conversation')->name('account.messages.show');
            Route::get('/account/messages/{conversation}/attachments/{media}', [ConversationController::class, 'attachment'])->whereNumber(['conversation', 'media'])->name('account.messages.attachment');

            Route::view('/account/bookings', 'pages.account.bookings')->name('account.bookings');
            Route::get('/account/hotels/{hotel}/rooms', HotelRoomsController::class)->whereNumber('hotel')->name('account.hotels.rooms');
            Route::view('/account/applications', 'pages.account.applications')->name('account.applications');
            Route::get('/account/applications/{application}/cv', ApplicationCvController::class)->whereNumber('application')->name('account.applications.cv');

            // Add-listing wizard; editing reuses it. Posting needs a verified phone.
            Route::middleware('phone.verified')->group(function (): void {
                Route::get('/add', [ListingWizardController::class, 'create'])->name('listings.create');
                Route::get('/account/listings/{type}/{id}/edit', [ListingWizardController::class, 'edit'])
                    ->whereIn('type', array_keys(OwnerListings::TYPES))->whereNumber('id')->name('listings.edit');
            });
        });
    });
});
