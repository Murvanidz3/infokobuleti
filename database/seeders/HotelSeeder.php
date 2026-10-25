<?php

namespace Database\Seeders;

use App\Enums\AmenityScope;
use App\Enums\BusinessType;
use App\Enums\HotelType;
use App\Enums\ListingStatus;
use App\Models\Amenity;
use App\Models\Hotel;
use App\Models\RoomAvailability;
use App\Models\RoomType;
use App\Models\RoomTypePriceRule;
use Database\Factories\RoomTypeFactory;
use Database\Factories\Support\Kobuleti;
use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * 30 places to stay with room types, seasonal prices, a few availability exceptions and amenities.
 */
class HotelSeeder extends Seeder
{
    use SeedsDemoData;

    /** @var array<int, ListingStatus> */
    private const array STATUSES = [27 => ListingStatus::Pending, 28 => ListingStatus::Paused, 29 => ListingStatus::Draft];

    /** @var array<int, float> */
    private const array STAR_FACTOR = [2 => 0.6, 3 => 0.8, 4 => 1.0, 5 => 1.5];

    /** @var array<string, list<array{ka: string, en: string, ru: string}>> */
    private const array DESCRIPTIONS = [
        'hotel' => [
            ['ka' => 'თანამედროვე სასტუმრო ზღვიდან {sea} მეტრში. ოთახებში კონდიციონერი და აივანია, ფასში შედის საუზმე „შვედური მაგიდის“ პრინციპით.', 'en' => 'A modern hotel {sea} metres from the sea. Rooms have air conditioning and balconies; buffet breakfast is included.', 'ru' => 'Современный отель в {sea} метрах от моря. В номерах кондиционер и балкон, завтрак «шведский стол» включён.'],
            ['ka' => 'სასტუმრო აუზით და მწვანე ეზოთი, ზღვამდე {sea} მეტრია. ოჯახებისთვის გვაქვს საოჯახო ოთახები და საბავშვო მოედანი.', 'en' => 'A hotel with a pool and a green courtyard, {sea} metres from the sea. Family rooms and a playground for kids.', 'ru' => 'Отель с бассейном и зелёным двором, до моря {sea} метров. Есть семейные номера и детская площадка.'],
        ],
        'guesthouse' => [
            ['ka' => 'მყუდრო საოჯახო სასტუმრო, სადაც მასპინძლები ყოველთვის დაგეხმარებიან. ზღვამდე {sea} მეტრია, ეზოში მაყალი და ხეხილის ბაღია.', 'en' => 'A cosy family guesthouse where the hosts are always happy to help. The sea is {sea} metres away; the yard has a barbecue and fruit trees.', 'ru' => 'Уютный семейный гостевой дом, хозяева всегда помогут. До моря {sea} метров, во дворе мангал и фруктовый сад.'],
            ['ka' => 'სუფთა და ნათელი ოთახები ხელმისაწვდომ ფასად, ზღვიდან {sea} მეტრში. სურვილის შემთხვევაში მოგიმზადებთ ქართულ საუზმეს და ვახშამს.', 'en' => 'Clean, bright rooms at fair prices, {sea} metres from the sea. Georgian breakfast and dinner on request.', 'ru' => 'Чистые и светлые номера по доступным ценам, в {sea} метрах от моря. По желанию приготовим грузинский завтрак и ужин.'],
        ],
        'apartment' => [
            ['ka' => 'სრულად აღჭურვილი აპარტამენტები სამზარეულოთი და აივნით, ზღვიდან {sea} მეტრში. იდეალურია ხანგრძლივი დასვენებისთვის.', 'en' => 'Fully equipped apartments with a kitchen and balcony, {sea} metres from the sea. Ideal for longer stays.', 'ru' => 'Полностью оборудованные апартаменты с кухней и балконом, в {sea} метрах от моря. Идеально для долгого отдыха.'],
        ],
        'cottage' => [
            ['ka' => 'ცალკე მდგომი კოტეჯები სიმშვიდისა და ბუნების მოყვარულთათვის. ეზოში მაყალი და ჰამაკებია.', 'en' => 'Detached cottages for those who love peace and nature. The garden has a barbecue and hammocks.', 'ru' => 'Отдельные коттеджи для любителей тишины и природы. Во дворе мангал и гамаки.'],
        ],
        'hostel' => [
            ['ka' => 'მეგობრული ჰოსტელი მოგზაურებისთვის: საერთო სამზარეულო, სწრაფი Wi-Fi და ველოსიპედების გაქირავება. ზღვამდე {sea} მეტრია.', 'en' => 'A friendly hostel for travellers: shared kitchen, fast Wi-Fi and bike rental. The sea is {sea} metres away.', 'ru' => 'Дружелюбный хостел для путешественников: общая кухня, быстрый Wi-Fi и прокат велосипедов. До моря {sea} метров.'],
        ],
    ];

    /** @var array<string, list<array{name: array{ka: string, en: string, ru: string}, adults: int, beds: list<array{type: string, count: int}>, size: int, price: int}>> */
    private const array OWN_ROOMS = [
        'apartment' => [
            ['name' => ['ka' => 'სტუდიო', 'en' => 'Studio', 'ru' => 'Студия'], 'adults' => 2, 'beds' => [['type' => 'double', 'count' => 1]], 'size' => 32, 'price' => 130],
            ['name' => ['ka' => 'ერთსაძინებლიანი აპარტამენტი', 'en' => 'One-Bedroom Apartment', 'ru' => 'Апартаменты с одной спальней'], 'adults' => 4, 'beds' => [['type' => 'double', 'count' => 1], ['type' => 'sofa', 'count' => 1]], 'size' => 48, 'price' => 190],
        ],
        'cottage' => [
            ['name' => ['ka' => 'მთლიანი კოტეჯი', 'en' => 'Whole Cottage', 'ru' => 'Коттедж целиком'], 'adults' => 6, 'beds' => [['type' => 'double', 'count' => 2], ['type' => 'single', 'count' => 2]], 'size' => 85, 'price' => 280],
        ],
        'hostel' => [
            ['name' => ['ka' => 'საწოლი 6-ადგილიან ოთახში', 'en' => 'Bed in 6-Bed Dorm', 'ru' => 'Место в 6-местном номере'], 'adults' => 1, 'beds' => [['type' => 'bunk', 'count' => 3]], 'size' => 24, 'price' => 30],
            ['name' => ['ka' => 'ორადგილიანი ოთახი', 'en' => 'Private Double Room', 'ru' => 'Двухместный номер'], 'adults' => 2, 'beds' => [['type' => 'double', 'count' => 1]], 'size' => 14, 'price' => 85],
        ],
    ];

    /** @var array<string, list<string>> */
    private const array BASE_AMENITIES = [
        'hotel' => ['wifi', 'reception-24h', 'breakfast', 'restaurant', 'elevator', 'parking'],
        'guesthouse' => ['wifi', 'garden', 'bbq'],
        'apartment' => ['wifi', 'parking', 'elevator'],
        'cottage' => ['wifi', 'parking', 'garden', 'bbq'],
        'hostel' => ['wifi', 'laundry'],
    ];

    /** @var Collection<string, int> */
    private Collection $hotelAmenities;

    /** @var Collection<string, int> */
    private Collection $roomAmenities;

    public function run(): void
    {
        $this->hotelAmenities = Amenity::query()->where('scope', AmenityScope::Hotel)->pluck('id', 'slug');
        $this->roomAmenities = Amenity::query()->where('scope', AmenityScope::Room)->pluck('id', 'slug');

        foreach (require __DIR__.'/data/hotels.php' as $position => $row) {
            $this->seedHotel($position, $row);
        }
    }

    /**
     * @param  array{name: array{ka: string, en: string, ru: string}, type: string, stars: int|null, sea: int, location: string, rooms: int, business: bool}  $row
     */
    private function seedHotel(int $position, array $row): void
    {
        $type = HotelType::from($row['type']);
        $place = $this->placeAt($row['location'], $row['location'] === 'kintrishi-protected-areas' ? 0.01 : 0.004);
        $profile = $row['business'] ? $this->createBusiness(BusinessType::Hotel, $row['name']['ka'], $place) : null;
        $seasonal = in_array($type, [HotelType::Guesthouse, HotelType::Hostel], true)
            || ($type === HotelType::Hotel && $row['stars'] <= 3)
            || $row['location'] === 'kintrishi-protected-areas';

        $hotel = $this->withDemoStatus(Hotel::factory(), $position, self::STATUSES)->create([
            'user_id' => $profile->user_id ?? $this->individuals()->random()->id,
            'business_profile_id' => $profile?->id,
            'title' => $row['name'],
            'description' => $this->describe($type, $row['sea'], $position),
            ...$place,
            'type' => $type,
            'stars' => $row['stars'],
            'distance_to_sea_m' => $row['sea'],
            'check_in_from' => $type === HotelType::Hostel ? '15:00' : '14:00',
            'check_out_until' => $type === HotelType::Hotel ? '12:00' : '11:00',
            'rooms_count' => $row['rooms'],
            'season_start_month' => $seasonal ? 5 : null,
            'season_end_month' => $seasonal ? 10 : null,
            'contact_phone' => $profile->phone ?? Kobuleti::phone(),
            'contact_email' => $profile?->email,
        ]);

        $hotel->amenities()->attach($this->amenitiesFor($type, $row['stars']));

        $this->seedRooms($hotel, $type, $row['stars'], $row['rooms'], $position);
    }

    /**
     * @return array{ka: string, en: string, ru: string}
     */
    private function describe(HotelType $type, int $sea, int $position): array
    {
        $variants = self::DESCRIPTIONS[$type->value];

        return array_map(fn (string $text) => str_replace('{sea}', (string) $sea, $text), $variants[$position % count($variants)]);
    }

    /**
     * @return list<int>
     */
    private function amenitiesFor(HotelType $type, ?int $stars): array
    {
        $slugs = self::BASE_AMENITIES[$type->value];

        if ($stars >= 4) {
            array_push($slugs, 'pool', 'bar', 'gym', 'airport-transfer', 'beach-area');
        }

        if ($stars === 5) {
            $slugs[] = 'spa';
        }

        $extras = $this->hotelAmenities->keys()->diff($slugs)->random(2)->all();

        return $this->hotelAmenities->only([...$slugs, ...$extras])->values()->all();
    }

    private function seedRooms(Hotel $hotel, HotelType $type, ?int $stars, int $rooms, int $position): void
    {
        $templates = match ($type) {
            HotelType::Hotel => fake()->randomElements(RoomTypeFactory::TEMPLATES, ($stars ?? 3) >= 4 ? 4 : 3),
            HotelType::Guesthouse => [RoomTypeFactory::TEMPLATES[0], RoomTypeFactory::TEMPLATES[1], RoomTypeFactory::TEMPLATES[3]],
            default => self::OWN_ROOMS[$type->value],
        };

        $factor = match ($type) {
            HotelType::Hotel => self::STAR_FACTOR[$stars ?? 3],
            HotelType::Guesthouse => 0.55,
            default => 1.0,
        };

        $season = now()->month > 8 ? now()->year + 1 : now()->year;

        foreach (array_values($templates) as $sort => $template) {
            $price = $this->roundPrice($template['price'] * $factor);

            $roomType = RoomType::query()->create([
                'hotel_id' => $hotel->id,
                'name' => $template['name'],
                'capacity_adults' => $template['adults'],
                'capacity_children' => $template['adults'] >= 4 ? 2 : ($template['adults'] === 1 ? 0 : 1),
                'beds' => $template['beds'],
                'size_m2' => $template['size'],
                'base_price' => $price,
                'units' => max(1, intdiv($rooms, count($templates))),
                'sort' => $sort,
            ]);

            $roomType->amenities()->attach($this->roomAmenitiesFor($type, $template['name']['en']));

            $minNights = $type === HotelType::Hotel ? 1 : 3;

            RoomTypePriceRule::query()->create([
                'room_type_id' => $roomType->id,
                'name' => ['ka' => 'მაღალი სეზონი', 'en' => 'High season', 'ru' => 'Высокий сезон'],
                'starts_on' => "{$season}-07-01",
                'ends_on' => "{$season}-08-31",
                'price' => $this->roundPrice($price * 1.4),
                'min_nights' => $minNights,
            ]);

            RoomTypePriceRule::query()->create([
                'room_type_id' => $roomType->id,
                'name' => ['ka' => 'ივნისი', 'en' => 'June', 'ru' => 'Июнь'],
                'starts_on' => "{$season}-06-01",
                'ends_on' => "{$season}-06-30",
                'price' => $this->roundPrice($price * 1.15),
                'min_nights' => 1,
            ]);

            if ($sort === 0 && $position % 3 === 0) {
                $this->seedAvailability($roomType->id, $price);
            }
        }
    }

    /**
     * @return list<int>
     */
    private function roomAmenitiesFor(HotelType $type, string $templateName): array
    {
        $slugs = ['private-bathroom', 'air-conditioning', 'tv', 'kettle'];

        if (str_contains($templateName, 'Sea View') || str_contains($templateName, 'Suite')) {
            array_push($slugs, 'sea-view', 'balcony');
        }

        if (in_array($type, [HotelType::Apartment, HotelType::Cottage], true)) {
            array_push($slugs, 'kitchenette', 'fridge');
        }

        if ($type === HotelType::Hotel) {
            array_push($slugs, 'hairdryer', 'safe');
        }

        if ($type === HotelType::Hostel && str_contains($templateName, 'Dorm')) {
            $slugs = ['air-conditioning'];
        }

        return $this->roomAmenities->only($slugs)->values()->all();
    }

    /**
     * A few closed dates and weekend surcharges, as owners would mark them in the calendar.
     */
    private function seedAvailability(int $roomTypeId, float $price): void
    {
        $dates = collect(range(10, 60))->random(5)->map(fn (int $days) => now()->addDays($days)->toDateString());

        foreach ($dates->values() as $index => $date) {
            RoomAvailability::query()->create([
                'room_type_id' => $roomTypeId,
                'date' => $date,
                'is_closed' => $index < 3,
                'price_override' => $index < 3 ? null : $this->roundPrice($price * 1.25),
            ]);
        }
    }

    private function roundPrice(float $price): int
    {
        return (int) (round($price / 5) * 5);
    }
}
