<?php

namespace Database\Factories;

use App\Enums\BuildingStatus;
use App\Enums\Currency;
use App\Enums\ListedBy;
use App\Enums\PropertyCondition;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Models\Property;
use App\Models\User;
use Database\Factories\Concerns\ListingStates;
use Database\Factories\Support\Kobuleti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    use ListingStates;

    /** @var list<array{ka: string, en: string, ru: string}> */
    private const array APARTMENT_FEATURES = [
        ['ka' => 'ახალ კორპუსში', 'en' => 'in a new building', 'ru' => 'в новостройке'],
        ['ka' => 'ზღვის ხედით', 'en' => 'with sea view', 'ru' => 'с видом на море'],
        ['ka' => 'ბულვართან ახლოს', 'en' => 'near the boulevard', 'ru' => 'рядом с бульваром'],
        ['ka' => 'ცენტრში, აივნით', 'en' => 'in the centre, with balcony', 'ru' => 'в центре, с балконом'],
        ['ka' => 'ავეჯით და ტექნიკით', 'en' => 'furnished, with appliances', 'ru' => 'с мебелью и техникой'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement([
            PropertyType::Apartment, PropertyType::Apartment, PropertyType::Apartment,
            PropertyType::House, PropertyType::Land,
            PropertyType::Commercial, PropertyType::Hotel,
        ]);
        $deal = match ($type) {
            PropertyType::Land => PropertyDeal::Sale,
            PropertyType::Hotel => fake()->randomElement([PropertyDeal::DailyRent, PropertyDeal::Sale]),
            default => fake()->randomElement(PropertyDeal::cases()),
        };

        return [
            ...$this->listingAttributes(),
            'user_id' => User::factory(),
            'address' => Kobuleti::address(),
            ...Kobuleti::coordinates(),
            'deal' => $deal,
            'type' => $type,
            'listed_by' => fake()->randomElement(ListedBy::cases()),
            'distance_to_sea_m' => fake()->numberBetween(30, 1500),
            'contact_name' => Kobuleti::firstName(),
            'contact_phone' => Kobuleti::phone(),
            ...$this->shape($type, $deal),
        ];
    }

    public function deal(PropertyDeal $deal): static
    {
        return $this->state(fn (array $attributes) => ['deal' => $deal, ...$this->shape($attributes['type'], $deal)]);
    }

    public function type(PropertyType $type, ?PropertyDeal $deal = null): static
    {
        return $this->state(function (array $attributes) use ($type, $deal) {
            $deal ??= $type === PropertyType::Land ? PropertyDeal::Sale : $attributes['deal'];

            return ['type' => $type, 'deal' => $deal, ...$this->shape($type, $deal)];
        });
    }

    /**
     * Size, price and title that make sense together for the type and deal.
     *
     * @return array<string, mixed>
     */
    private function shape(PropertyType $type, PropertyDeal $deal): array
    {
        $rooms = fake()->numberBetween(1, 4);
        $isApartment = $type === PropertyType::Apartment;
        $floor = fake()->numberBetween(1, 14);

        $area = match ($type) {
            PropertyType::Apartment => $rooms * fake()->numberBetween(20, 30) + 10,
            PropertyType::House => fake()->numberBetween(90, 260),
            PropertyType::Land => null,
            PropertyType::Commercial => fake()->numberBetween(40, 300),
            PropertyType::Hotel => fake()->numberBetween(300, 1500),
        };

        [$price, $currency] = match ($deal) {
            PropertyDeal::Sale, PropertyDeal::Pledge => [match ($type) {
                PropertyType::Land => fake()->numberBetween(15, 200) * 1000,
                PropertyType::Hotel => fake()->numberBetween(25, 120) * 10000,
                default => (int) round($area * fake()->numberBetween(850, 1400), -3),
            }, Currency::USD],
            PropertyDeal::Rent => [fake()->numberBetween(8, 30) * 100, Currency::GEL],
            PropertyDeal::DailyRent => [fake()->numberBetween(8, 30) * 10, Currency::GEL],
        };

        return [
            'title' => $this->title($type, $rooms),
            'description' => $this->description($type),
            'price' => $price,
            'currency' => $currency,
            'area_m2' => $area,
            'land_area_m2' => $type->allows('land_area_m2') ? fake()->numberBetween(3, 30) * 100 : null,
            'rooms' => $type->allows('rooms') ? $rooms + ($type === PropertyType::House ? 2 : 0) : null,
            'bedrooms' => $type->allows('bedrooms') ? max(1, $rooms - 1) : null,
            'bathrooms' => $type->allows('bathrooms') ? fake()->numberBetween(1, 2) : null,
            'floor' => $unitFloor = $type->allows('floor') ? ($isApartment ? $floor : fake()->numberBetween(1, 2)) : null,
            'total_floors' => $type->allows('total_floors')
                ? ($isApartment ? fake()->numberBetween($floor, 16) : fake()->numberBetween(max(1, $unitFloor ?? 1), 3))
                : null,
            'condition' => $type->allows('condition') ? fake()->randomElement(PropertyCondition::cases()) : null,
            'building_status' => $type->allows('building_status') ? fake()->randomElement(BuildingStatus::cases()) : null,
            'cadastral_code' => $type === PropertyType::Land ? '20.'.fake()->numerify('##.##.###') : null,
        ];
    }

    /**
     * @return array{ka: string, en: string, ru: string}
     */
    private function title(PropertyType $type, int $rooms): array
    {
        $feature = fake()->randomElement(self::APARTMENT_FEATURES);

        return match ($type) {
            PropertyType::Apartment => [
                'ka' => "{$rooms}-ოთახიანი ბინა {$feature['ka']}",
                'en' => "{$rooms}-room apartment {$feature['en']}",
                'ru' => "{$rooms}-комнатная квартира {$feature['ru']}",
            ],
            PropertyType::House => fake()->randomElement([
                ['ka' => 'კერძო სახლი ბაღით', 'en' => 'Private house with a garden', 'ru' => 'Частный дом с садом'],
                ['ka' => 'ორსართულიანი სახლი ეზოთი', 'en' => 'Two-storey house with a yard', 'ru' => 'Двухэтажный дом с двором'],
                ['ka' => 'სახლი ზღვასთან ახლოს', 'en' => 'House close to the sea', 'ru' => 'Дом недалеко от моря'],
            ]),
            PropertyType::Land => fake()->randomElement([
                ['ka' => 'სამშენებლო ნაკვეთი', 'en' => 'Building plot', 'ru' => 'Участок под застройку'],
                ['ka' => 'ნაკვეთი ზღვასთან ახლოს', 'en' => 'Plot close to the sea', 'ru' => 'Участок недалеко от моря'],
                ['ka' => 'სასოფლო-სამეურნეო მიწა', 'en' => 'Agricultural land', 'ru' => 'Сельскохозяйственная земля'],
                ['ka' => 'ნაკვეთი მთავარ გზაზე', 'en' => 'Plot on the main road', 'ru' => 'Участок у главной дороги'],
            ]),
            PropertyType::Commercial => fake()->randomElement([
                ['ka' => 'კომერციული ფართი პირველ სართულზე', 'en' => 'Ground-floor commercial space', 'ru' => 'Коммерческое помещение на первом этаже'],
                ['ka' => 'მაღაზიის ფართი ცენტრში', 'en' => 'Retail space in the centre', 'ru' => 'Торговое помещение в центре'],
                ['ka' => 'ოფისი ბულვართან ახლოს', 'en' => 'Office near the boulevard', 'ru' => 'Офис рядом с бульваром'],
            ]),
            PropertyType::Hotel => fake()->randomElement([
                ['ka' => 'მოქმედი საოჯახო სასტუმრო', 'en' => 'Operating guesthouse', 'ru' => 'Действующий гостевой дом'],
                ['ka' => 'სასტუმრო 15 ოთახით', 'en' => 'Hotel with 15 rooms', 'ru' => 'Гостиница на 15 номеров'],
            ]),
        };
    }

    /**
     * @return array{ka: string, en: string, ru: string}
     */
    private function description(PropertyType $type): array
    {
        return match ($type) {
            PropertyType::Land => [
                'ka' => 'ნაკვეთი სწორი რელიეფით, მიყვანილია დენი და წყალი. საკადასტრო კოდი მოწესრიგებულია.',
                'en' => 'A level plot with electricity and water connected. Cadastral records are in order.',
                'ru' => 'Ровный участок, подведены свет и вода. Кадастровые документы в порядке.',
            ],
            PropertyType::Commercial, PropertyType::Hotel => [
                'ka' => 'გამოსადეგია ბიზნესისთვის: მაღალი გამვლელობა, ცალკე შესასვლელი და ყველა კომუნიკაცია.',
                'en' => 'Suited to business: busy foot traffic, a separate entrance and all utilities.',
                'ru' => 'Подходит под бизнес: высокая проходимость, отдельный вход и все коммуникации.',
            ],
            default => fake()->randomElement([
                [
                    'ka' => 'ნათელი და მყუდრო ფართი ქობულეთში. ახლოსაა ზღვა, მაღაზიები და სკოლა. დეტალებისთვის დამირეკეთ.',
                    'en' => 'A bright, cosy place in Kobuleti, close to the sea, shops and a school. Call for details.',
                    'ru' => 'Светлое и уютное жильё в Кобулети: рядом море, магазины и школа. Звоните, расскажу подробнее.',
                ],
                [
                    'ka' => 'მშვიდი ქუჩა, კარგი მეზობლები და ზღვამდე რამდენიმე წუთის სავალი. შესაფერისია როგორც საცხოვრებლად, ისე გასაქირავებლად.',
                    'en' => 'A quiet street, good neighbours and a few minutes on foot to the sea. Good to live in or to rent out.',
                    'ru' => 'Тихая улица, хорошие соседи и несколько минут пешком до моря. Подходит и для жизни, и для сдачи.',
                ],
            ]),
        };
    }
}
