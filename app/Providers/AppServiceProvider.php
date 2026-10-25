<?php

namespace App\Providers;

use App\Contracts\ExchangeRates;
use App\Models;
use App\Services\Currency\FixedExchangeRates;
use App\Services\LocaleRouter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

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
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LocaleRouter::class);

        $this->app->singleton(ExchangeRates::class, fn () => new FixedExchangeRates(config('currency.fallback_rates')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Relation::enforceMorphMap(self::MORPH_MAP);
    }
}
