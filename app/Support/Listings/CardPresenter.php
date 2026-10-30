<?php

namespace App\Support\Listings;

use App\Enums\Currency;
use App\Enums\PropertyDeal;
use App\Enums\SalaryPeriod;
use App\Enums\SiteFeature;
use App\Models\Article;
use App\Models\BusinessProfile;
use App\Models\Classified;
use App\Models\Event;
use App\Models\Place;
use App\Models\Project;
use App\Models\Property;
use App\Models\Vacancy;
use App\Services\DateFormatter;
use App\Services\Favorites;
use App\Services\PriceFormatter;
use App\Support\Features;
use App\View\Components\Icon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Turns models into the display-ready arrays the card components take.
 *
 * Callers must eager-load "media" and "location" (plus "category" / "businessProfile" where used).
 */
class CardPresenter
{
    public function __construct(
        private PriceFormatter $price,
        private DateFormatter $dates,
        private Favorites $favorites,
    ) {}

    /**
     * @return array{favorite: string, deal: string, badge: string, price: string, converted: ?string, title: string, location: string, features: list<array{icon: string, text: string}>, url: string, image: ?string, saved: bool, lat: ?float, lng: ?float, pin: string, promotion: ?string, logo: ?string, ownerVerified: bool}
     */
    public function property(Property $property): array
    {
        $currency = $this->currency($property->currency);
        $amount = (float) $property->price;
        $suffix = match ($property->deal) {
            PropertyDeal::Rent => ' '.__('listing.per_month'),
            PropertyDeal::DailyRent => ' '.__('listing.per_day'),
            PropertyDeal::Sale, PropertyDeal::Pledge => '',
        };
        $converted = null;

        if ($currency !== 'GEL' && $property->price_gel !== null) {
            $converted = __('listing.approx', ['price' => $this->price->format((float) $property->price_gel)]);
        }

        return [
            'favorite' => $this->favoriteKey($property),
            'deal' => match ($property->deal) {
                PropertyDeal::Sale, PropertyDeal::Pledge => 'sale',
                PropertyDeal::Rent, PropertyDeal::DailyRent => 'rent',
            },
            'badge' => (string) $property->deal->getLabel(),
            'price' => $this->price->format($amount, $currency).$suffix,
            'converted' => $converted,
            'title' => (string) $property->title,
            'location' => $this->locationText($property),
            'features' => $this->propertyFeatures($property),
            'url' => localized_route('real-estate.show', [$property]),
            'image' => $property->coverUrl(),
            'saved' => $this->favorites->isSaved($property),
            'lat' => $this->coordinate($property->lat),
            'lng' => $this->coordinate($property->lng),
            'pin' => $this->price->compact($amount, $currency),
            'promotion' => $this->promotion($property),
            'logo' => $this->packageLogo($property),
            'ownerVerified' => $property->owner_verified_at !== null && Features::active(SiteFeature::TrustLayer),
        ];
    }

    /**
     * @return list<array{icon: string, text: string}>
     */
    public function propertyFeatures(Property $property): array
    {
        $features = [];

        if ($property->area_m2 !== null) {
            $features[] = ['icon' => 'area', 'text' => __('listing.area', ['value' => $this->price->number((float) $property->area_m2)])];
        }

        if ($property->rooms !== null) {
            $features[] = ['icon' => 'door', 'text' => trans_choice('listing.rooms', (int) $property->rooms, ['count' => $property->rooms])];
        }

        if ($property->floor !== null && $property->total_floors !== null) {
            $features[] = ['icon' => 'layers', 'text' => __('listing.floor', ['floor' => $property->floor, 'total' => $property->total_floors])];
        } elseif ($property->land_area_m2 !== null && $property->type->allows('land_area_m2')) {
            $features[] = ['icon' => 'house', 'text' => __('listing.yard', ['value' => $this->price->number((float) $property->land_area_m2)])];
        }

        return $features;
    }

    /**
     * @return array{favorite: string, badge: string, price: string, converted: ?string, title: string, location: string, features: list<array{icon: string, text: string}>, url: string, image: ?string, saved: bool, lat: ?float, lng: ?float, pin: string, promotion: ?string, logo: ?string}
     */
    public function project(Project $project): array
    {
        $currency = $this->currency($project->currency);
        $amount = (float) $project->price_per_m2;
        $converted = $currency !== 'GEL' && $project->price_gel !== null
            ? __('listing.approx', ['price' => $this->price->format((float) $project->price_gel).' / m²'])
            : null;
        $features = [['icon' => 'building', 'text' => (string) $project->developer]];

        if ($project->completion_on !== null) {
            $features[] = ['icon' => 'calendar', 'text' => $project->completion_on->format('m.Y')];
        }

        return [
            'favorite' => $this->favoriteKey($project),
            'badge' => __('site.projects.badge'),
            'price' => $this->price->format($amount, $currency).' / m²',
            'converted' => $converted,
            'title' => (string) $project->title,
            'location' => $this->locationText($project),
            'features' => $features,
            'url' => localized_route('projects.show', [$project]),
            'image' => $project->coverUrl(),
            'saved' => $this->favorites->isSaved($project),
            'lat' => $this->coordinate($project->lat),
            'lng' => $this->coordinate($project->lng),
            'pin' => $this->price->compact($amount, $currency),
            'promotion' => $this->promotion($project),
            'logo' => $this->packageLogo($project),
        ];
    }

    /**
     * @return array{favorite: string, title: string, meta: string, price: string, time: string, icon: string, isNew: bool, url: string, image: ?string, saved: bool, lat: ?float, lng: ?float, pin: ?string, promotion: ?string}
     */
    public function classified(Classified $classified): array
    {
        $category = $classified->category;
        $published = $classified->published_at ?? $classified->created_at ?? Carbon::now();

        return [
            'favorite' => $this->favoriteKey($classified),
            'title' => (string) $classified->title,
            'meta' => collect([$category?->name, $this->locationText($classified)])->filter()->implode(' · '),
            'price' => $this->classifiedPrice($classified),
            'time' => $this->dates->relative($published),
            'icon' => collect([$category?->icon, $category?->parent?->icon])->first(fn (?string $icon): bool => Icon::has($icon)) ?? 'tag',
            'isNew' => $published->greaterThan(Carbon::now()->subDay()),
            'url' => localized_route('classifieds.show', [$classified]),
            'image' => $classified->coverUrl('thumb'),
            'saved' => $this->favorites->isSaved($classified),
            'lat' => $this->coordinate($classified->lat),
            'lng' => $this->coordinate($classified->lng),
            'pin' => $classified->price !== null ? $this->price->compact((float) $classified->price, $this->currency($classified->currency)) : null,
            'promotion' => $this->promotion($classified),
        ];
    }

    public function classifiedPrice(Classified $classified): string
    {
        if ($classified->price === null) {
            return __('site.price.on_request');
        }

        return $this->price->format((float) $classified->price, $this->currency($classified->currency));
    }

    /**
     * @return array{favorite: string, title: string, employer: string, salary: string, period: string, type: string, seasonal: bool, posted: string, url: string, lat: ?float, lng: ?float, pin: ?string, promotion: ?string, logo: ?string}
     */
    public function job(Vacancy $vacancy): array
    {
        return [
            'favorite' => $this->favoriteKey($vacancy),
            'title' => (string) $vacancy->title,
            'employer' => $vacancy->businessProfile->name ?? $this->locationText($vacancy),
            'salary' => $this->salary($vacancy),
            'period' => $vacancy->salary_min !== null || $vacancy->salary_max !== null ? __('site.salary_period.'.$vacancy->salary_period->value) : '',
            'type' => (string) $vacancy->employment_type->getLabel(),
            'seasonal' => $vacancy->employment_type->value === 'seasonal',
            'posted' => $this->dates->relativeDay($vacancy->published_at ?? $vacancy->created_at ?? Carbon::now()),
            'url' => localized_route('jobs.show', [$vacancy]),
            'lat' => $this->coordinate($vacancy->lat),
            'lng' => $this->coordinate($vacancy->lng),
            'pin' => $vacancy->salary_min !== null ? $this->price->compact((float) $vacancy->salary_min, $vacancy->salary_currency->value) : null,
            'promotion' => $this->promotion($vacancy),
            'logo' => $this->packageLogo($vacancy),
        ];
    }

    public function salary(Vacancy $vacancy): string
    {
        $currency = $vacancy->salary_currency->value;

        return match (true) {
            $vacancy->salary_min !== null && $vacancy->salary_max !== null && $vacancy->salary_min !== $vacancy->salary_max => $this->price->range($vacancy->salary_min, $vacancy->salary_max, $currency),
            $vacancy->salary_min !== null => $this->price->format($vacancy->salary_min, $currency),
            $vacancy->salary_max !== null => __('site.salary_up_to', ['salary' => $this->price->format($vacancy->salary_max, $currency)]),
            default => __('site.salary_negotiable'),
        };
    }

    public function salaryPeriod(SalaryPeriod $period): string
    {
        return __('site.salary_period.'.$period->value);
    }

    /**
     * @return array{favorite: string, tag: string, title: string, description: ?string, meta: string, url: string, image: ?string, lat: ?float, lng: ?float, pin: ?string}
     */
    public function guide(Place $place): array
    {
        $meta = collect([
            $place->rating_avg !== null ? '★ '.number_format((float) $place->rating_avg, 1) : null,
            $this->locationText($place),
        ])->filter()->implode(' · ');

        return [
            'favorite' => $this->favoriteKey($place),
            'tag' => (string) $place->type->getLabel(),
            'title' => (string) $place->title,
            'description' => $this->excerpt($place->description, 140),
            'meta' => $meta,
            'url' => localized_route('guide.show', [$place]),
            'image' => $place->coverUrl(),
            'lat' => $this->coordinate($place->lat),
            'lng' => $this->coordinate($place->lng),
            'pin' => null,
        ];
    }

    /**
     * @return array{tag: string, date: string, title: string, url: string, image: ?string}
     */
    public function news(Article $article): array
    {
        return [
            'tag' => (string) ($article->category->name ?? __('nav.news')),
            'date' => $this->dates->date($article->published_at ?? $article->created_at ?? Carbon::now()),
            'title' => (string) $article->title,
            'url' => localized_route('news.show', [$article]),
            'image' => $article->coverUrl(),
        ];
    }

    /**
     * @return array{favorite: string, tag: string, title: string, day: string, month: string, when: string, venue: string, price: string, url: string, image: ?string, lat: ?float, lng: ?float, pin: ?string}
     */
    public function event(Event $event): array
    {
        $starts = $event->starts_at;

        return [
            'favorite' => $this->favoriteKey($event),
            'tag' => (string) ($event->category->name ?? __('nav.events')),
            'title' => (string) $event->title,
            'day' => $starts->format('j'),
            'month' => $this->dates->monthShort($starts),
            'when' => $event->is_all_day ? $this->dates->weekdayDate($starts) : $this->dates->weekdayDate($starts).' · '.$this->dates->time($starts),
            'venue' => $event->venue ? (string) $event->venue : ($event->place?->title ? (string) $event->place->title : $this->locationText($event)),
            'price' => $event->is_free ? __('site.events.free') : ($event->price_from !== null ? __('listing.price_from', ['price' => $this->price->format((float) $event->price_from)]) : ''),
            'url' => localized_route('events.show', [$event]),
            'image' => $event->coverUrl(),
            'lat' => $this->coordinate($event->lat),
            'lng' => $this->coordinate($event->lng),
            'pin' => null,
        ];
    }

    /**
     * "super_vip" / "vip" for promoted listings (highlighted card and badge), otherwise null.
     */
    public function promotion(Model $listing): ?string
    {
        return match ((int) $listing->getAttribute('promotion_level')) {
            2 => 'super_vip',
            1 => 'vip',
            default => null,
        };
    }

    /**
     * The business logo shown on cards of business package holders. Only read when the caller
     * eager-loaded "businessProfile.media".
     */
    public function packageLogo(Model $listing): ?string
    {
        if (! $listing->relationLoaded('businessProfile')) {
            return null;
        }

        $profile = $listing->getRelation('businessProfile');

        if (! $profile instanceof BusinessProfile || ! $profile->hasPackage() || ! $profile->relationLoaded('media')) {
            return null;
        }

        $url = $profile->getFirstMediaUrl('logo');

        return $url !== '' ? $url : null;
    }

    public function sea(mixed $meters): ?string
    {
        if ($meters === null) {
            return null;
        }

        $meters = (int) $meters;

        return $meters >= 1000
            ? __('site.to_sea_km', ['distance' => $this->price->number(round($meters / 1000, 1))])
            : __('listing.to_sea', ['distance' => $this->price->number($meters)]);
    }

    public function excerpt(mixed $text, int $length = 160): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)) ?? '');

        return $text === '' ? null : Str::limit($text, $length);
    }

    public function favoriteKey(Model $model): string
    {
        return $model->getMorphClass().':'.$model->getKey();
    }

    /**
     * "Batumi street 5, Kobuleti centre": the street address and the location name, whatever exists.
     */
    public function locationText(Model $model): string
    {
        $address = $model->getAttribute('address');
        $address = is_string($address) ? $address : null;

        if (method_exists($model, 'getTranslation') && in_array('address', $model->translatable ?? [], true)) {
            $address = $model->getTranslation('address', app()->getLocale()) ?: null;
        }

        $location = $model->relationLoaded('location') ? $model->getRelation('location')?->name : null;

        return collect([$address, $location])->filter()->unique()->implode(', ') ?: __('site.kobuleti');
    }

    /**
     * ISO code of a currency column (cast to the Currency enum on most models).
     */
    public function currency(mixed $value): string
    {
        return $value instanceof Currency ? $value->value : (is_string($value) && $value !== '' ? $value : 'GEL');
    }

    private function coordinate(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
