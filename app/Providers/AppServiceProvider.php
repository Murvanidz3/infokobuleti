<?php

namespace App\Providers;

use App\Contracts\ExchangeRates;
use App\Contracts\HumanVerifier;
use App\Contracts\SmsGateway;
use App\Enums\Role;
use App\Http\Middleware\SetLocale;
use App\Models;
use App\Services\Auth\CloudflareTurnstile;
use App\Services\Auth\FakeHumanVerifier;
use App\Services\Auth\Impersonation;
use App\Services\Auth\SessionTracker;
use App\Services\Currency\FixedExchangeRates;
use App\Services\LocaleRouter;
use App\Services\Settings;
use App\Services\Sms\SmsManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Short aliases stored in every polymorphic *_type column (and in media, roles, activity log).
     *
     * @var array<string, class-string<Model>>
     */
    public const array MORPH_MAP = [
        'user' => Models\User::class,
        'business_profile' => Models\BusinessProfile::class,
        'location' => Models\Location::class,
        'category' => Models\Category::class,
        'amenity' => Models\Amenity::class,
        'hotel' => Models\Hotel::class,
        'room_type' => Models\RoomType::class,
        'booking_request' => Models\BookingRequest::class,
        'property' => Models\Property::class,
        'classified' => Models\Classified::class,
        'vacancy' => Models\Vacancy::class,
        'vacancy_application' => Models\VacancyApplication::class,
        'place' => Models\Place::class,
        'article' => Models\Article::class,
        'event' => Models\Event::class,
        'review' => Models\Review::class,
        'conversation' => Models\Conversation::class,
        'message' => Models\Message::class,
        'promotion' => Models\Promotion::class,
        'payment' => Models\Payment::class,
        'user_session' => Models\UserSession::class,
        'page' => Models\Page::class,
        'banner' => Models\Banner::class,
        'price_plan' => Models\PricePlan::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LocaleRouter::class);

        $this->app->singleton(ExchangeRates::class, fn () => new FixedExchangeRates(config('currency.fallback_rates')));

        $this->app->singleton(SmsManager::class);
        $this->app->singleton(Settings::class);
        $this->app->bind(SmsGateway::class, fn ($app) => $app->make(SmsManager::class)->driver());

        $this->app->singleton(HumanVerifier::class, function (): HumanVerifier {
            $siteKey = config('services.turnstile.site_key');
            $secretKey = config('services.turnstile.secret_key');

            return filled($siteKey) && filled($secretKey) ? new CloudflareTurnstile($siteKey, $secretKey) : new FakeHumanVerifier;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Relation::enforceMorphMap(self::MORPH_MAP);

        // super_admin passes every authorization check.
        Gate::before(fn (Models\User $user) => $user->hasRole(Role::SuperAdmin->value) ? true : null);

        Event::listen(Login::class, function (Login $event): void {
            if ($event->guard === 'web' && $event->user instanceof Models\User && app()->bound('request') && request()->hasSession() && ! request()->session()->has(Impersonation::SESSION_KEY)) {
                app(SessionTracker::class)->start(request(), $event->user, $event->remember);
            }
        });

        RateLimiter::for('otp', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // Livewire update requests keep the language of the page they came from.
        Livewire::addPersistentMiddleware([SetLocale::class]);
    }
}
