<?php

namespace App\Support\Listings;

use App\Enums\AmenityScope;
use Illuminate\Support\Facades\DB;

/**
 * Maps property rows written before the ss.ge taxonomy onto the current enums,
 * and inserts property amenities that were not in the original catalog.
 *
 * Rollback cannot tell a former "needs renovation" row from one that was already
 * "old renovation", and a hotel created after the migration rolls back to hotel_business.
 */
final class PropertyLegacyValues
{
    /** @var array<string, string> */
    public const array TYPES = [
        'hotel_business' => 'hotel',
    ];

    /** @var array<string, string> */
    public const array CONDITIONS = [
        'needs_renovation' => 'old_renovation',
    ];

    /**
     * New enum values, mapped back to a value the previous code accepts.
     *
     * @var array<string, string>
     */
    public const array TYPE_ROLLBACK = [
        'hotel' => 'hotel_business',
        'country_house' => 'house',
    ];

    /** @var array<string, string> */
    public const array CONDITION_ROLLBACK = [
        'renovated' => 'newly_renovated',
    ];

    /** @var array<string, string> */
    public const array DEAL_ROLLBACK = [
        'pledge' => 'sale',
    ];

    /** @var list<string> */
    public const array ADDED_AMENITY_SLUGS = [
        'basement',
        'cable-tv',
        'drinking-water',
        'fridge',
        'double-glazing',
        'tv',
        'iron-door',
    ];

    public static function apply(): void
    {
        foreach (self::TYPES as $from => $to) {
            DB::table('properties')->where('type', $from)->update(['type' => $to]);
        }

        foreach (self::CONDITIONS as $from => $to) {
            DB::table('properties')->where('condition', $from)->update(['condition' => $to]);
        }

        self::syncAmenities();
    }

    public static function rollback(): void
    {
        foreach (self::TYPE_ROLLBACK as $from => $to) {
            DB::table('properties')->where('type', $from)->update(['type' => $to]);
        }

        foreach (self::CONDITION_ROLLBACK as $from => $to) {
            DB::table('properties')->where('condition', $from)->update(['condition' => $to]);
        }

        foreach (self::DEAL_ROLLBACK as $from => $to) {
            DB::table('properties')->where('deal', $from)->update(['deal' => $to]);
        }

        $ids = DB::table('amenities')
            ->where('scope', AmenityScope::Property->value)
            ->whereIn('slug', self::ADDED_AMENITY_SLUGS)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('amenityables')->whereIn('amenity_id', $ids)->delete();
            DB::table('amenities')->whereIn('id', $ids)->delete();
        }

        /** @var array{ka: string, en: string, ru: string} $internet */
        $internet = require database_path('seeders/data/property_internet_previous.php');

        DB::table('amenities')
            ->where('scope', AmenityScope::Property->value)
            ->where('slug', 'internet')
            ->update([
                'name' => json_encode($internet, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }

    /**
     * Inserts the ss.ge amenities and refreshes the internet label from the catalog.
     */
    public static function syncAmenities(): void
    {
        /** @var array<string, list<array{0: string, 1: string|null, 2: string|null, 3: string, 4: string, 5: string}>> $catalog */
        $catalog = require database_path('seeders/data/amenities.php');
        $wanted = [...self::ADDED_AMENITY_SLUGS, 'internet'];

        foreach ($catalog['property'] as $sort => $row) {
            [$slug, $group, $icon, $ka, $en, $ru] = $row;

            if (! in_array($slug, $wanted, true)) {
                continue;
            }

            self::upsertAmenity($slug, $group, $icon, compact('ka', 'en', 'ru'), (int) $sort);
        }
    }

    /**
     * @param  array{ka: string, en: string, ru: string}  $name
     */
    private static function upsertAmenity(string $slug, ?string $group, ?string $icon, array $name, int $sort): void
    {
        $payload = [
            'group' => $group,
            'icon' => $icon,
            'name' => json_encode($name, JSON_UNESCAPED_UNICODE),
            'sort' => $sort,
            'is_filterable' => true,
            'updated_at' => now(),
        ];

        $existing = DB::table('amenities')
            ->where('scope', AmenityScope::Property->value)
            ->where('slug', $slug)
            ->value('id');

        if ($existing === null) {
            DB::table('amenities')->insert([
                ...$payload,
                'scope' => AmenityScope::Property->value,
                'slug' => $slug,
                'created_at' => now(),
            ]);

            return;
        }

        DB::table('amenities')->where('id', $existing)->update($payload);
    }
}
