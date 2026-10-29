<?php

namespace App\Services\Seo;

use App\Enums\EmploymentType;
use App\Enums\HotelType;
use App\Enums\PlaceType;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Enums\SalaryPeriod;
use App\Models\Article;
use App\Models\BusinessProfile;
use App\Models\Classified;
use App\Models\Event;
use App\Models\Hotel;
use App\Models\Place;
use App\Models\Property;
use App\Models\Vacancy;
use App\Services\PriceFormatter;
use App\Support\Listings\CardPresenter;
use App\Support\Listings\PublicUrl;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Search-engine data for a public page: schema.org JSON-LD, the share image and the values the
 * meta templates can use. Phone numbers are never included: they are only shown on request.
 *
 * @phpstan-type Seo array{page: string, vars: array<string, string|null>, image: ?string, type: string, schema: list<array<string, mixed>>}
 */
final class StructuredData
{
    private const string CONTEXT = 'https://schema.org';

    public function __construct(private CardPresenter $cards, private PriceFormatter $prices, private OgImage $og) {}

    /**
     * @return Seo
     */
    public function for(Model $record): array
    {
        $seo = match (true) {
            $record instanceof Hotel => $this->hotel($record),
            $record instanceof Property => $this->property($record),
            $record instanceof Vacancy => $this->vacancy($record),
            $record instanceof Classified => $this->classified($record),
            $record instanceof Event => $this->event($record),
            $record instanceof Article => $this->article($record),
            $record instanceof Place => $this->place($record),
            $record instanceof BusinessProfile => $this->business($record),
            default => ['page' => 'page', 'vars' => [], 'image' => null, 'type' => 'website', 'schema' => []],
        };

        // The share picture: generated from the cover photo, title and price.
        $seo['image'] = $this->og->url($record, $seo['vars']);

        return $seo;
    }

    /**
     * The site itself (homepage): organization and the site search box.
     *
     * @return list<array<string, mixed>>
     */
    public function site(): array
    {
        $home = localized_route('home');
        $socials = array_values(array_filter(array_map(
            fn (string $network): mixed => site_setting("social.{$network}"),
            ['facebook', 'instagram', 'youtube', 'telegram', 'tiktok'],
        )));

        return [
            array_filter([
                '@context' => self::CONTEXT,
                '@type' => 'Organization',
                '@id' => $home.'#organization',
                'name' => MetaTemplates::SITE,
                'url' => $home,
                'logo' => url('/icons/icon-512.png'),
                'email' => site_setting('contacts.email', config('site.contact.email')),
                'address' => $this->address(null),
                'sameAs' => $socials ?: null,
            ]),
            [
                '@context' => self::CONTEXT,
                '@type' => 'WebSite',
                'name' => MetaTemplates::SITE,
                'url' => $home,
                'inLanguage' => app()->getLocale(),
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => ['@type' => 'EntryPoint', 'urlTemplate' => localized_route('search').'?q={search_term_string}'],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];
    }

    /**
     * @return Seo
     */
    private function hotel(Hotel $hotel): array
    {
        $hotel->loadMissing(['media', 'location']);
        $images = $this->images($hotel);
        $schema = [
            '@context' => self::CONTEXT,
            '@type' => match ($hotel->type) {
                HotelType::Hotel => 'Hotel',
                HotelType::Hostel => 'Hostel',
                HotelType::Guesthouse => 'BedAndBreakfast',
                default => 'LodgingBusiness',
            },
            'name' => (string) $hotel->title,
            'description' => $this->text($hotel->description, 500),
            'url' => PublicUrl::for($hotel),
            'image' => $images ?: null,
            'address' => $this->address($hotel),
            'geo' => $this->geo($hotel),
            'starRating' => $hotel->stars ? ['@type' => 'Rating', 'ratingValue' => (int) $hotel->stars] : null,
            'aggregateRating' => $this->rating($hotel->rating_avg, $hotel->reviews_count),
            'checkinTime' => $hotel->check_in_from ? substr((string) $hotel->check_in_from, 0, 5) : null,
            'checkoutTime' => $hotel->check_out_until ? substr((string) $hotel->check_out_until, 0, 5) : null,
            'numberOfRooms' => $hotel->rooms_count ?: null,
            'priceRange' => $hotel->price_from ? __('listing.price_from', ['price' => $this->prices->format((float) $hotel->price_from)]) : null,
            'makesOffer' => $hotel->price_from ? [
                '@type' => 'Offer',
                'price' => round((float) $hotel->price_from, 2),
                'priceCurrency' => 'GEL',
                'priceSpecification' => ['@type' => 'UnitPriceSpecification', 'price' => round((float) $hotel->price_from, 2), 'priceCurrency' => 'GEL', 'unitCode' => 'DAY'],
            ] : null,
        ];

        return $this->result('hotel', $schema, $images, 'website', [
            'type' => (string) $hotel->type->getLabel(),
            'price' => $hotel->price_from ? $this->prices->format((float) $hotel->price_from) : null,
            'location' => $this->cards->locationText($hotel),
        ]);
    }

    /**
     * @return Seo
     */
    private function property(Property $property): array
    {
        $property->loadMissing(['media', 'location']);
        $images = $this->images($property);
        $currency = $property->currency;
        $price = $property->price !== null ? (float) $property->price : null;
        $home = array_filter([
            '@type' => match ($property->type) {
                PropertyType::Apartment => 'Apartment',
                PropertyType::House, PropertyType::CountryHouse => 'House',
                PropertyType::Land => 'Landform',
                PropertyType::Hotel => 'Hotel',
                PropertyType::Commercial => 'Accommodation',
            },
            'name' => (string) $property->title,
            'floorSize' => $property->area_m2 ? ['@type' => 'QuantitativeValue', 'value' => $property->area_m2, 'unitCode' => 'MTK'] : null,
            'numberOfRooms' => $property->rooms ?: null,
            'numberOfBedrooms' => $property->bedrooms ?: null,
            'numberOfBathroomsTotal' => $property->bathrooms ?: null,
            'floorLevel' => $property->floor !== null ? (string) $property->floor : null,
            'address' => $this->address($property),
            'geo' => $this->geo($property),
        ], fn (mixed $value): bool => $value !== null);

        $schema = [
            '@context' => self::CONTEXT,
            '@type' => 'RealEstateListing',
            'name' => (string) $property->title,
            'description' => $this->text($property->description, 500),
            'url' => PublicUrl::for($property),
            'image' => $images ?: null,
            'datePosted' => $this->date($property->published_at),
            'about' => $home,
            'offers' => $price !== null ? [
                '@type' => 'Offer',
                'price' => round($price, 2),
                'priceCurrency' => $currency,
                'availability' => 'https://schema.org/InStock',
                'businessFunction' => match ($property->deal) {
                    PropertyDeal::Sale, PropertyDeal::Pledge => 'https://purl.org/goodrelations/v1#Sell',
                    PropertyDeal::Rent, PropertyDeal::DailyRent => 'https://purl.org/goodrelations/v1#LeaseOut',
                },
            ] : null,
        ];

        return $this->result('property', $schema, $images, 'website', [
            'type' => $property->type->getLabel().' · '.$property->deal->getLabel(),
            'price' => $price !== null ? $this->prices->format($price, $this->cards->currency($currency)) : null,
            'location' => $this->cards->locationText($property),
        ]);
    }

    /**
     * Google Jobs: title, description, dates, employer, place and salary.
     *
     * @return Seo
     */
    private function vacancy(Vacancy $vacancy): array
    {
        $vacancy->loadMissing(['businessProfile.media', 'user', 'location']);
        $profile = $vacancy->business_profile_id !== null ? $vacancy->businessProfile : null;
        $employer = $profile instanceof BusinessProfile ? $profile->name : ($vacancy->user->name ?? MetaTemplates::SITE);
        $logo = $profile instanceof BusinessProfile ? ($profile->getFirstMediaUrl('logo') ?: null) : null;
        $validThrough = $vacancy->deadline?->copy()->endOfDay() ?? $vacancy->getAttribute('expires_at');
        $currency = $vacancy->salary_currency->value ?? 'GEL';

        $salary = null;

        if ($vacancy->salary_min || $vacancy->salary_max) {
            $salary = [
                '@type' => 'MonetaryAmount',
                'currency' => $currency,
                'value' => array_filter([
                    '@type' => 'QuantitativeValue',
                    'minValue' => $vacancy->salary_min ?: null,
                    'maxValue' => $vacancy->salary_max ?: null,
                    'value' => $vacancy->salary_min && ! $vacancy->salary_max ? $vacancy->salary_min : null,
                    'unitText' => match ($vacancy->salary_period) {
                        SalaryPeriod::Hour => 'HOUR',
                        SalaryPeriod::Day => 'DAY',
                        default => 'MONTH',
                    },
                ]),
            ];
        }

        $schema = [
            '@context' => self::CONTEXT,
            '@type' => 'JobPosting',
            'title' => (string) $vacancy->title,
            'description' => nl2br(e($this->text($vacancy->description, 5000) ?? (string) $vacancy->title)),
            'datePosted' => $this->date($vacancy->published_at),
            'validThrough' => $this->date($validThrough),
            'employmentType' => match ($vacancy->employment_type) {
                EmploymentType::FullTime => 'FULL_TIME',
                EmploymentType::PartTime => 'PART_TIME',
                EmploymentType::Seasonal => 'TEMPORARY',
                EmploymentType::Remote => 'FULL_TIME',
            },
            'jobLocationType' => $vacancy->employment_type === EmploymentType::Remote ? 'TELECOMMUTE' : null,
            'applicantLocationRequirements' => $vacancy->employment_type === EmploymentType::Remote ? ['@type' => 'Country', 'name' => 'GE'] : null,
            'hiringOrganization' => array_filter([
                '@type' => 'Organization',
                'name' => $employer,
                'sameAs' => $profile instanceof BusinessProfile ? ($profile->website ?: PublicUrl::for($profile)) : null,
                'logo' => $logo,
            ]),
            'jobLocation' => ['@type' => 'Place', 'address' => $this->address($vacancy)],
            'baseSalary' => $salary,
            'directApply' => true,
            'url' => PublicUrl::for($vacancy),
        ];

        return $this->result('job', $schema, $logo !== null ? [$logo] : [], 'website', [
            'type' => $vacancy->employment_type->getLabel(),
            'price' => $vacancy->salary_min ? $this->prices->format((float) $vacancy->salary_min, $this->cards->currency($currency)) : null,
            'location' => $employer,
        ]);
    }

    /**
     * @return Seo
     */
    private function classified(Classified $classified): array
    {
        $classified->loadMissing(['media', 'location']);
        $images = $this->images($classified);
        $price = $classified->price !== null ? (float) $classified->price : null;
        $currency = $classified->currency->value;

        $schema = [
            '@context' => self::CONTEXT,
            '@type' => 'Product',
            'name' => (string) $classified->title,
            'description' => $this->text($classified->description, 500),
            'url' => PublicUrl::for($classified),
            'image' => $images ?: null,
            'offers' => $price !== null ? [
                '@type' => 'Offer',
                'price' => round($price, 2),
                'priceCurrency' => $currency,
                'availability' => 'https://schema.org/InStock',
                'url' => PublicUrl::for($classified),
            ] : null,
        ];

        return $this->result('classified', $price !== null && $images !== [] ? $schema : null, $images, 'website', [
            'price' => $price !== null ? $this->prices->format($price, $this->cards->currency($currency)) : null,
            'location' => $this->cards->locationText($classified),
        ]);
    }

    /**
     * @return Seo
     */
    private function event(Event $event): array
    {
        $event->loadMissing(['media', 'place', 'location']);
        $images = $this->images($event);
        $venue = trim((string) $event->venue) ?: ($event->place->title ?? __('site.kobuleti'));

        $schema = [
            '@context' => self::CONTEXT,
            '@type' => 'Event',
            'name' => (string) $event->title,
            'description' => $this->text($event->description, 500),
            'url' => PublicUrl::for($event),
            'image' => $images ?: null,
            'startDate' => $event->is_all_day ? $event->starts_at->toDateString() : $this->date($event->starts_at),
            'endDate' => $event->ends_at !== null ? ($event->is_all_day ? $event->ends_at->toDateString() : $this->date($event->ends_at)) : null,
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => array_filter(['@type' => 'Place', 'name' => $venue, 'address' => $this->address($event->place ?? $event), 'geo' => $this->geo($event->place ?? $event)]),
            'organizer' => filled($event->organizer) ? ['@type' => 'Organization', 'name' => (string) $event->organizer] : ['@type' => 'Organization', 'name' => MetaTemplates::SITE, 'url' => localized_route('home')],
            'offers' => [
                '@type' => 'Offer',
                'price' => $event->is_free ? 0 : round((float) ($event->price_from ?? 0), 2),
                'priceCurrency' => 'GEL',
                'url' => $event->ticket_url ?: PublicUrl::for($event),
                'availability' => 'https://schema.org/InStock',
                'validFrom' => $this->date($event->published_at),
            ],
        ];

        return $this->result('event', $schema, $images, 'website', [
            'date' => $event->starts_at->locale(app()->getLocale())->translatedFormat('j F Y'),
            'location' => $venue,
            'price' => $event->is_free ? __('site.events.free') : ($event->price_from ? $this->prices->format((float) $event->price_from) : null),
        ]);
    }

    /**
     * @return Seo
     */
    private function article(Article $article): array
    {
        $article->loadMissing(['media', 'author']);
        $images = $this->images($article);
        $publisher = [
            '@type' => 'Organization',
            'name' => MetaTemplates::SITE,
            'url' => localized_route('home'),
            'logo' => ['@type' => 'ImageObject', 'url' => url('/icons/icon-512.png')],
        ];

        $schema = [
            '@context' => self::CONTEXT,
            '@type' => 'NewsArticle',
            'headline' => mb_strimwidth((string) $article->title, 0, 110, '…'),
            'description' => $this->text($article->excerpt ?: $article->body, 300),
            'url' => localized_route('news.show', [$article]),
            'mainEntityOfPage' => localized_route('news.show', [$article]),
            'image' => $images ?: null,
            'datePublished' => $this->date($article->published_at),
            'dateModified' => $this->date($article->updated_at ?? $article->published_at),
            'inLanguage' => app()->getLocale(),
            'author' => $article->author !== null ? ['@type' => 'Person', 'name' => $article->author->name] : $publisher,
            'publisher' => $publisher,
        ];

        return $this->result('article', $schema, $images, 'article', [
            'date' => $article->published_at?->locale(app()->getLocale())->translatedFormat('j F Y'),
        ]);
    }

    /**
     * @return Seo
     */
    private function place(Place $place): array
    {
        $place->loadMissing(['media', 'location']);
        $images = $this->images($place);

        $schema = [
            '@context' => self::CONTEXT,
            '@type' => match ($place->type) {
                PlaceType::Beach => 'Beach',
                PlaceType::Museum => 'Museum',
                PlaceType::Restaurant => 'Restaurant',
                PlaceType::Cafe => 'CafeOrCoffeeShop',
                PlaceType::Nature => 'Park',
                PlaceType::Service => 'LocalBusiness',
                default => 'TouristAttraction',
            },
            'name' => (string) $place->title,
            'description' => $this->text($place->description, 500),
            'url' => PublicUrl::for($place),
            'image' => $images ?: null,
            'address' => $this->address($place),
            'geo' => $this->geo($place),
            'aggregateRating' => $this->rating($place->rating_avg, $place->reviews_count),
        ];

        return $this->result('place', $schema, $images, 'website', [
            'type' => $place->type->getLabel(),
            'location' => $this->cards->locationText($place),
        ]);
    }

    /**
     * @return Seo
     */
    private function business(BusinessProfile $profile): array
    {
        $profile->loadMissing(['media', 'location']);
        $logo = $profile->getFirstMediaUrl('logo') ?: null;
        $sameAs = array_values(array_filter([$profile->website, ...array_values((array) $profile->socials)], fn (mixed $url): bool => is_string($url) && str_starts_with($url, 'http')));

        $schema = [
            '@context' => self::CONTEXT,
            '@type' => 'LocalBusiness',
            'name' => $profile->name,
            'description' => $this->text($profile->description, 500),
            'url' => PublicUrl::for($profile),
            'logo' => $logo,
            'image' => $logo,
            'email' => $profile->email ?: null,
            'address' => $this->address($profile),
            'geo' => $this->geo($profile),
            'sameAs' => $sameAs ?: null,
            'aggregateRating' => $this->rating($profile->getAttribute('rating_avg'), $profile->getAttribute('reviews_count')),
        ];

        return $this->result('business', $schema, $logo !== null ? [$logo] : [], 'profile', [
            'type' => $profile->type->getLabel(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @param  list<string>  $images
     * @param  array<string, string|null>  $vars
     * @return Seo
     */
    private function result(string $page, ?array $schema, array $images, string $type, array $vars): array
    {
        return [
            'page' => $page,
            'vars' => $vars,
            'image' => $images[0] ?? null,
            'type' => $type,
            'schema' => $schema === null ? [] : [$this->clean($schema)],
        ];
    }

    /**
     * Absolute image URLs, largest size first.
     *
     * @return list<string>
     */
    private function images(HasMedia $model): array
    {
        return $model->getMedia('gallery')
            ->take(5)
            ->map(fn (Media $media): string => $media->getAvailableFullUrl(['large', 'card']) ?: $media->getFullUrl())
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function address(?Model $model): array
    {
        $street = $model !== null && $model->getAttribute('address') !== null ? $this->cards->locationText($model) : null;

        return array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $street,
            'addressLocality' => __('site.kobuleti'),
            'addressRegion' => __('seo.adjara'),
            'addressCountry' => 'GE',
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function geo(?Model $model): ?array
    {
        $lat = $model?->getAttribute('lat');
        $lng = $model?->getAttribute('lng');

        return $lat !== null && $lng !== null ? ['@type' => 'GeoCoordinates', 'latitude' => (float) $lat, 'longitude' => (float) $lng] : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function rating(mixed $average, mixed $count): ?array
    {
        return (int) $count > 0 && (float) $average > 0
            ? ['@type' => 'AggregateRating', 'ratingValue' => round((float) $average, 1), 'bestRating' => 10, 'worstRating' => 1, 'reviewCount' => (int) $count]
            : null;
    }

    private function text(mixed $value, int $length): ?string
    {
        return $this->cards->excerpt($value, $length);
    }

    private function date(?CarbonInterface $date): ?string
    {
        return $date?->toIso8601String();
    }

    /**
     * Drops empty values at every level.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function clean(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->clean($value);
                $data[$key] = $value;
            }

            if ($value === null || $value === '' || $value === []) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
