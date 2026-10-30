<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum PropertyType: string implements FilamentLabel
{
    use HasLabel;

    case Apartment = 'apartment';
    case House = 'house';
    case Land = 'land';
    case Commercial = 'commercial';
    case Hotel = 'hotel';

    /**
     * Fields this type collects. Anything else is rejected and stored as null.
     * Land has no rooms or floor; an apartment has no yard.
     *
     * @return array<string, 'required'|'optional'>
     */
    public function fields(): array
    {
        $shared = [
            'cadastral_code' => 'optional',
        ];

        return match ($this) {
            self::Apartment => [
                ...$shared,
                'area_m2' => 'required',
                'rooms' => 'required',
                'bedrooms' => 'optional',
                'bathrooms' => 'optional',
                'floor' => 'optional',
                'total_floors' => 'optional',
                'condition' => 'optional',
                'building_status' => 'optional',
            ],
            self::House => [
                ...$shared,
                'area_m2' => 'required',
                'land_area_m2' => 'optional',
                'rooms' => 'required',
                'bedrooms' => 'optional',
                'bathrooms' => 'optional',
                'total_floors' => 'optional',
                'condition' => 'optional',
                'building_status' => 'optional',
            ],
            self::Land => [
                ...$shared,
                'land_area_m2' => 'required',
            ],
            self::Commercial => [
                ...$shared,
                'area_m2' => 'required',
                'rooms' => 'optional',
                'bathrooms' => 'optional',
                'floor' => 'optional',
                'total_floors' => 'optional',
                'condition' => 'optional',
                'building_status' => 'optional',
            ],
            self::Hotel => [
                ...$shared,
                'area_m2' => 'required',
                'land_area_m2' => 'optional',
                'rooms' => 'optional',
                'bathrooms' => 'optional',
                'floor' => 'optional',
                'total_floors' => 'optional',
                'condition' => 'optional',
                'building_status' => 'optional',
            ],
        };
    }

    public function allows(string $field): bool
    {
        return array_key_exists($field, $this->fields());
    }

    public function requires(string $field): bool
    {
        return ($this->fields()[$field] ?? null) === 'required';
    }
}
