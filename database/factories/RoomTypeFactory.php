<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomType>
 */
class RoomTypeFactory extends Factory
{
    /** @var list<array{name: array{ka: string, en: string, ru: string}, adults: int, beds: list<array{type: string, count: int}>, size: int, price: int}> */
    public const array TEMPLATES = [
        ['name' => ['ka' => 'სტანდარტული ორადგილიანი', 'en' => 'Standard Double', 'ru' => 'Стандартный двухместный'], 'adults' => 2, 'beds' => [['type' => 'double', 'count' => 1]], 'size' => 18, 'price' => 120],
        ['name' => ['ka' => 'ორადგილიანი ცალკე საწოლებით', 'en' => 'Twin Room', 'ru' => 'Двухместный с раздельными кроватями'], 'adults' => 2, 'beds' => [['type' => 'twin', 'count' => 1]], 'size' => 18, 'price' => 120],
        ['name' => ['ka' => 'ოთახი ზღვის ხედით', 'en' => 'Sea View Room', 'ru' => 'Номер с видом на море'], 'adults' => 2, 'beds' => [['type' => 'double', 'count' => 1]], 'size' => 22, 'price' => 170],
        ['name' => ['ka' => 'საოჯახო ოთახი', 'en' => 'Family Room', 'ru' => 'Семейный номер'], 'adults' => 4, 'beds' => [['type' => 'double', 'count' => 1], ['type' => 'single', 'count' => 2]], 'size' => 30, 'price' => 210],
        ['name' => ['ka' => 'ლუქსი აივნით', 'en' => 'Suite with Balcony', 'ru' => 'Люкс с балконом'], 'adults' => 2, 'beds' => [['type' => 'double', 'count' => 1], ['type' => 'sofa', 'count' => 1]], 'size' => 38, 'price' => 290],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $template = fake()->randomElement(self::TEMPLATES);

        return [
            'hotel_id' => Hotel::factory(),
            'name' => $template['name'],
            'capacity_adults' => $template['adults'],
            'capacity_children' => $template['adults'] >= 4 ? 2 : 1,
            'beds' => $template['beds'],
            'size_m2' => $template['size'],
            'base_price' => $template['price'] + fake()->randomElement([-30, -10, 0, 10, 20]),
            'units' => fake()->numberBetween(1, 8),
        ];
    }
}
