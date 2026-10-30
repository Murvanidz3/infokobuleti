<?php

namespace Database\Seeders\Concerns;

use App\Enums\BusinessType;
use App\Enums\ListingStatus;
use App\Enums\Role;
use App\Models\BusinessProfile;
use App\Models\Location;
use App\Models\User;
use Database\Factories\ClassifiedFactory;
use Database\Factories\ProjectFactory;
use Database\Factories\PropertyFactory;
use Database\Factories\Support\Kobuleti;
use Database\Factories\VacancyFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Shared helpers for the demo seeders: placing listings in real Kobuleti locations, creating
 * business owners and picking demo users.
 */
trait SeedsDemoData
{
    /** Locations whose listings get a street address rather than just the settlement name. */
    private const array TOWN_LOCATIONS = ['kobuleti', 'kobuleti-centre', 'north-kobuleti', 'south-kobuleti'];

    /** @var array<string, array{ka: string, en: string, ru: string}> */
    private const array BUSINESS_DESCRIPTIONS = [
        'hotel' => ['ka' => 'სასტუმრო ქობულეთში, ზღვასთან ახლოს. ვმუშაობთ მთელი წლის განმავლობაში.', 'en' => 'A hotel in Kobuleti, close to the sea. Open all year round.', 'ru' => 'Отель в Кобулети недалеко от моря. Работаем круглый год.'],
        'agency' => ['ka' => 'უძრავი ქონების სააგენტო: ყიდვა, გაყიდვა და გაქირავება ქობულეთსა და მის შემოგარენში.', 'en' => 'Real estate agency: buying, selling and renting in Kobuleti and around.', 'ru' => 'Агентство недвижимости: покупка, продажа и аренда в Кобулети и окрестностях.'],
        'employer' => ['ka' => 'ადგილობრივი კომპანია, რომელიც მუდმივად ეძებს ახალ თანამშრომლებს.', 'en' => 'A local company that is always looking for new people.', 'ru' => 'Местная компания, которая всегда ищет новых сотрудников.'],
        'service' => ['ka' => 'სანდო მომსახურება ქობულეთში, ჩაქვსა და ციხისძირში.', 'en' => 'Reliable services in Kobuleti, Chakvi and Tsikhisdziri.', 'ru' => 'Надёжные услуги в Кобулети, Чакви и Цихисдзири.'],
        'restaurant' => ['ka' => 'ქართული და აჭარული სამზარეულო, ახალი პროდუქტები ადგილობრივი ფერმერებისგან.', 'en' => 'Georgian and Adjarian cuisine made with fresh produce from local farmers.', 'ru' => 'Грузинская и аджарская кухня из свежих продуктов местных фермеров.'],
    ];

    /** @var Collection<string, Location>|null */
    private ?Collection $locationsBySlug = null;

    /** @var Collection<int, User>|null */
    private ?Collection $individualUsers = null;

    protected function location(string $slug): Location
    {
        $this->locationsBySlug ??= Location::query()->get()->keyBy('slug');

        return $this->locationsBySlug->get($slug) ?? throw new InvalidArgumentException("Unknown location [{$slug}].");
    }

    /**
     * Location id, a point near the location's centre and an address.
     *
     * @return array{location_id: int, lat: float, lng: float, address: array<string, string>}
     */
    protected function placeAt(string $slug, float $radius = 0.004): array
    {
        $location = $this->location($slug);

        return [
            'location_id' => $location->id,
            ...Kobuleti::near((float) $location->lat, (float) $location->lng, $radius),
            'address' => in_array($slug, self::TOWN_LOCATIONS, true) ? Kobuleti::address() : $location->getTranslations('name'),
        ];
    }

    /**
     * A verified business profile with its own business owner.
     *
     * @param  array{location_id: int, lat: float, lng: float, address: array<string, string>}  $place
     */
    protected function createBusiness(BusinessType $type, string $name, array $place): BusinessProfile
    {
        $owner = User::factory()->business()->create();
        $owner->assignRole(Role::Business->value);

        return BusinessProfile::factory()->for($owner)->type($type)->verified()->create([
            'name' => $name,
            'description' => self::BUSINESS_DESCRIPTIONS[$type->value],
            'email' => 'info@'.Str::slug($name).'.example.com',
            ...$place,
        ]);
    }

    /**
     * Active individual demo users (role "user", not blocked).
     *
     * @return Collection<int, User>
     */
    protected function individuals(): Collection
    {
        return $this->individualUsers ??= User::role(Role::User->value)->where('is_blocked', false)->get();
    }

    /**
     * Applies a non-published demo status to every n-th listing so moderation queues have content.
     *
     * @template TFactory of PropertyFactory|ClassifiedFactory|VacancyFactory|ProjectFactory
     *
     * @param  TFactory  $factory
     * @param  array<int, ListingStatus>  $statuses  position => status
     * @return TFactory
     */
    protected function withDemoStatus(Factory $factory, int $position, array $statuses): Factory
    {
        return match ($statuses[$position] ?? null) {
            null => $factory,
            ListingStatus::Expired => $factory->withStatus(ListingStatus::Expired, ['published_at' => now()->subDays(75), 'expires_at' => now()->subDays(15)]),
            ListingStatus::Rejected => $factory->withStatus(ListingStatus::Rejected, ['published_at' => null, 'rejection_reason' => 'ფოტოები არ შეესაბამება განცხადებას.']),
            ListingStatus::Paused => $factory->withStatus(ListingStatus::Paused, ['published_at' => now()->subDays(40)]),
            default => $factory->withStatus($statuses[$position], ['published_at' => null]),
        };
    }
}
