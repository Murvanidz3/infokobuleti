<?php

namespace Database\Factories\Support;

use App\Models\Location;

/**
 * Local flavour for factories: streets, coordinates and phone numbers of Kobuleti.
 */
final class Kobuleti
{
    /**
     * Real streets of Kobuleti town.
     *
     * @var list<array{ka: string, en: string, ru: string}>
     */
    public const array STREETS = [
        ['ka' => 'შ. რუსთაველის ქ.', 'en' => 'Sh. Rustaveli St.', 'ru' => 'ул. Ш. Руставели'],
        ['ka' => 'დ. აღმაშენებლის გამზ.', 'en' => 'D. Aghmashenebeli Ave.', 'ru' => 'пр. Д. Агмашенебели'],
        ['ka' => 'ი. ჭავჭავაძის ქ.', 'en' => 'I. Chavchavadze St.', 'ru' => 'ул. И. Чавчавадзе'],
        ['ka' => 'თამარ მეფის ქ.', 'en' => 'Tamar Mepe St.', 'ru' => 'ул. Царицы Тамар'],
        ['ka' => 'მ. კოსტავას ქ.', 'en' => 'M. Kostava St.', 'ru' => 'ул. М. Костава'],
        ['ka' => 'ნ. ბარათაშვილის ქ.', 'en' => 'N. Baratashvili St.', 'ru' => 'ул. Н. Бараташвили'],
        ['ka' => 'ზ. გამსახურდიას ქ.', 'en' => 'Z. Gamsakhurdia St.', 'ru' => 'ул. З. Гамсахурдиа'],
        ['ka' => 'ი. ჯავახიშვილის ქ.', 'en' => 'I. Javakhishvili St.', 'ru' => 'ул. И. Джавахишвили'],
        ['ka' => 'ჩაქვის ქ.', 'en' => 'Chakvi St.', 'ru' => 'ул. Чакви'],
        ['ka' => 'აკ. წერეთლის ქ.', 'en' => 'A. Tsereteli St.', 'ru' => 'ул. А. Церетели'],
    ];

    /** @var list<string> */
    public const array FIRST_NAMES = [
        'გიორგი', 'დავით', 'ლევან', 'ირაკლი', 'ნიკა', 'ზურაბ', 'თემურ', 'ჯემალ', 'რევაზ', 'მამუკა',
        'ოთარ', 'ბექა', 'ლაშა', 'გელა', 'ნუგზარ', 'ავთანდილ', 'ნინო', 'თამარ', 'მარიამ', 'ეკა',
        'ნატო', 'ხათუნა', 'ლია', 'მანანა', 'სალომე', 'ანა', 'ქეთევან', 'ნანა', 'მაკა', 'ნათია',
    ];

    /**
     * Surnames common in Adjara.
     *
     * @var list<string>
     */
    public const array LAST_NAMES = [
        'ბერიძე', 'ხალვაში', 'ცინცაძე', 'შარაშიძე', 'ბოლქვაძე', 'დიასამიძე', 'აბაშიძე', 'ჯაყელი',
        'ქათამაძე', 'სურმანიძე', 'ტაკიძე', 'ნაკაშიძე', 'მიქელაძე', 'ფუტკარაძე', 'ვარშანიძე', 'გოგიტიძე',
        'ზოიძე', 'მამულაძე', 'კონცელიძე', 'ხიმშიაშვილი',
    ];

    /**
     * Town centre and a bounding box around the inhabited coast (centre, north and Tsikhisdziri side).
     */
    public const float CENTER_LAT = 41.8200;

    public const float CENTER_LNG = 41.7753;

    /**
     * @return array{ka: string, en: string, ru: string}
     */
    public static function address(?int $number = null): array
    {
        $street = fake()->randomElement(self::STREETS);
        $number ??= fake()->numberBetween(1, 260);

        return [
            'ka' => "{$street['ka']} {$number}",
            'en' => "{$number} {$street['en']}",
            'ru' => "{$street['ru']}, {$number}",
        ];
    }

    /**
     * A point near the town, east of the shoreline.
     *
     * @return array{lat: float, lng: float}
     */
    public static function coordinates(): array
    {
        return [
            'lat' => round(fake()->randomFloat(7, 41.7900, 41.8650), 7),
            'lng' => round(fake()->randomFloat(7, 41.7740, 41.8000), 7),
        ];
    }

    /**
     * A point within roughly $radius degrees (0.005 ≈ 500 m) of the given centre.
     *
     * @return array{lat: float, lng: float}
     */
    public static function near(float $lat, float $lng, float $radius = 0.005): array
    {
        return [
            'lat' => round($lat + fake()->randomFloat(7, -$radius, $radius), 7),
            'lng' => round($lng + fake()->randomFloat(7, -$radius, $radius), 7),
        ];
    }

    public static function firstName(): string
    {
        return fake()->randomElement(self::FIRST_NAMES);
    }

    public static function name(): string
    {
        return self::firstName().' '.fake()->randomElement(self::LAST_NAMES);
    }

    /**
     * A Georgian mobile number in E.164: +995 5XX XX XX XX.
     */
    public static function phone(): string
    {
        return '+9955'.fake()->randomElement(['55', '57', '58', '59', '91', '93', '95', '97', '98', '99']).fake()->numerify('######');
    }

    public static function locationId(): ?int
    {
        return Location::query()->inRandomOrder()->value('id');
    }

    /**
     * @return array{ka: string, en: string, ru: string}
     */
    public static function same(string $text): array
    {
        return ['ka' => $text, 'en' => $text, 'ru' => $text];
    }
}
