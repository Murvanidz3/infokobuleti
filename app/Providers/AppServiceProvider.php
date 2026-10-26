<?php

namespace App\Providers;

use App\Contracts\ExchangeRates;
use App\Contracts\Geocoder;
use App\Contracts\HumanVerifier;
use App\Contracts\PaymentGateway;
use App\Contracts\PushSender;
use App\Contracts\SmsGateway;
use App\Contracts\TelegramBot;
use App\Enums\Role;
use App\Http\Middleware\SetLocale;
use App\Models;
use App\Policies\ListingPolicy;
use App\Search\PostgresSearchEngine;
use App\Search\SearchSynonyms;
use App\Services\Auth\CloudflareTurnstile;
use App\Services\Auth\FakeHumanVerifier;
use App\Services\Auth\Impersonation;
use App\Services\Auth\SessionTracker;
use App\Services\Currency\FixedExchangeRates;
use App\Services\Currency\NationalBankExchangeRates;
use App\Services\Favorites;
use App\Services\Geo\FakeGeocoder;
use App\Services\Geo\NominatimGeocoder;
use App\Services\LocaleRouter;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Push\FakePushSender;
use App\Services\Push\WebPushSender;
use App\Services\Settings;
use App\Services\Sms\SmsManager;
use App\Services\Telegram\FakeTelegramBot;
use App\Services\Telegram\HttpTelegramBot;
use App\Support\Features;
use App\Support\Listings\PublicCache;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use Livewire\Livewire;
use Minishlink\WebPush\WebPush;

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
        'contact_message' => Models\ContactMessage::class,
        'listing_draft' => Models\ListingDraft::class,
        'order' => Models\Order::class,
        'business_subscription' => Models\BusinessSubscription::class,
    ];

    /**
     * Models shown on public pages whose changes invalidate PublicCache.
     *
     * @var list<class-string<Model>>
     */
    private const array PUBLIC_MODELS = [
        Models\Hotel::class, Models\RoomType::class, Models\Property::class, Models\Classified::class, Models\Vacancy::class,
        Models\Place::class, Models\Article::class, Models\Event::class, Models\Category::class, Models\Setting::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LocaleRouter::class);

        $this->app->singleton(FixedExchangeRates::class, fn () => new FixedExchangeRates(config('currency.fallback_rates')));
        $this->app->singleton(ExchangeRates::class, NationalBankExchangeRates::class);
        $this->app->scoped(Favorites::class);

        $this->app->singleton(SmsManager::class);
        $this->app->singleton(PaymentGatewayManager::class);
        $this->app->singleton(TelegramBot::class, fn (): TelegramBot => filled(config('services.telegram.bot_token'))
            ? new HttpTelegramBot((string) config('services.telegram.bot_token'), config('services.telegram.bot_username'))
            : new FakeTelegramBot);
        $this->app->singleton(PushSender::class, fn (): PushSender => filled(config('webpush.vapid.public_key')) && filled(config('webpush.vapid.private_key')) && class_exists(WebPush::class)
            ? new WebPushSender((array) config('webpush.vapid'))
            : new FakePushSender);
        $this->app->bind(PaymentGateway::class, fn ($app) => $app->make(PaymentGatewayManager::class)->driver());
        $this->app->singleton(Settings::class);
        $this->app->bind(SmsGateway::class, fn ($app) => $app->make(SmsManager::class)->driver());

        $this->app->singleton(Geocoder::class, fn (): Geocoder => config('geo.geocoder.driver') === 'nominatim'
            ? new NominatimGeocoder(config('geo.geocoder.url'), config('geo.geocoder.user_agent'), config('geo.geocoder.viewbox'))
            : new FakeGeocoder);

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

        $this->logSlowQueries();

        Features::define();

        Relation::enforceMorphMap(self::MORPH_MAP);

        // super_admin passes every authorization check.
        Gate::before(fn (Models\User $user) => $user->hasRole(Role::SuperAdmin->value) ? true : null);

        // Owners manage their own listings from the account area.
        foreach ([Models\Hotel::class, Models\Property::class, Models\Classified::class, Models\Vacancy::class] as $listing) {
            Gate::policy($listing, ListingPolicy::class);
        }

        Event::listen(Login::class, function (Login $event): void {
            if ($event->guard === 'web' && $event->user instanceof Models\User && app()->bound('request') && request()->hasSession() && ! request()->session()->has(Impersonation::SESSION_KEY)) {
                app(SessionTracker::class)->start(request(), $event->user, $event->remember);
            }
        });

        RateLimiter::for('otp', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // Livewire update requests keep the language of the page they came from.
        Livewire::addPersistentMiddleware([SetLocale::class]);

        $this->app->make(EngineManager::class)->extend('pgsql', fn ($app) => new PostgresSearchEngine($app->make(SearchSynonyms::class)));

        // Public pages cache listing blocks for a few minutes; any change makes them stale at once.
        foreach (self::PUBLIC_MODELS as $model) {
            $model::saved(fn () => PublicCache::bump());
            $model::deleted(fn () => PublicCache::bump());
        }

        RateLimiter::for('phone-reveal', fn (Request $request) => [
            Limit::perMinute(10)->by('reveal:'.($request->user()?->getAuthIdentifier() ?? $request->ip())),
            Limit::perDay(80)->by('reveal-day:'.($request->user()?->getAuthIdentifier() ?? $request->ip())),
        ]);
        RateLimiter::for('contact', fn (Request $request) => [
            Limit::perMinute(3)->by('contact:'.$request->ip()),
            Limit::perDay(20)->by('contact-day:'.$request->ip()),
        ]);
        RateLimiter::for('interactions', fn (Request $request) => Limit::perMinute(20)->by('interact:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(90)->by('search:'.$request->ip()));
        RateLimiter::for('livewire', fn (Request $request) => Limit::perMinute(300)->by('livewire:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));
        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(120)->by('webhooks:'.$request->ip()));

        // Every Livewire request (filters, forms, uploads) is rate-limited like the other endpoints.
        Livewire::setUpdateRoute(fn ($handle, string $path) => Route::post($path, $handle)->middleware(['web', 'throttle:livewire']));
    }

    /**
     * Queries slower than seo.slow_query_ms go to storage/logs/slow-queries-*.log with the page.
     */
    private function logSlowQueries(): void
    {
        $threshold = (int) config('seo.slow_query_ms');

        if ($threshold <= 0) {
            return;
        }

        DB::listen(function (QueryExecuted $query) use ($threshold): void {
            if ($query->time >= $threshold) {
                Log::channel('slow-queries')->warning(sprintf('%.0f ms', $query->time), [
                    'sql' => $query->sql,
                    'url' => app()->runningInConsole() ? 'console' : request()->fullUrl(),
                ]);
            }
        });
    }
}
