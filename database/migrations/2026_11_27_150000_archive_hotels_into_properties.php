<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A hotel is a property listing (deal daily_rent, type hotel). Copies each hotels row into
 * properties, repoints live links, and renames the booking tables to archive_*. Nothing is dropped.
 *
 * down() puts the five tables back, points every moved link at the original hotel id, deletes the
 * copied properties, and restores reviews, drafts, saved searches, and the homepage pick list.
 */
return new class extends Migration
{
    /** @var list<array{0: string, 1: string, 2: string}> table, type column, id column */
    private const array MORPHS = [
        ['media', 'model_type', 'model_id'],
        ['favorites', 'favoritable_type', 'favoritable_id'],
        ['reports', 'reportable_type', 'reportable_id'],
        ['daily_stats', 'statable_type', 'statable_id'],
        ['promotions', 'promotable_type', 'promotable_id'],
        ['conversations', 'subject_type', 'subject_id'],
        ['amenityables', 'amenityable_type', 'amenityable_id'],
        ['activity_log', 'subject_type', 'subject_id'],
        ['saved_search_hits', 'listing_type', 'listing_id'],
    ];

    /** @var list<array{0: string, 1: string}> live name, archive name */
    private const array TABLES = [
        ['booking_requests', 'archive_booking_requests'],
        ['room_availability', 'archive_room_availability'],
        ['room_type_price_rules', 'archive_room_type_price_rules'],
        ['room_types', 'archive_room_types'],
        ['hotels', 'archive_hotels'],
    ];

    public function up(): void
    {
        $this->mappingTables();
        $this->snapshotSavedSearches();
        $this->snapshotDrafts();
        $this->snapshotFeaturedHotels();
        $this->snapshotFeaturedPlan();

        $this->copyHotels();
        $this->repointMorphs(reverse: false);
        $this->archiveReviews();
        $this->rewriteSavedSearches();
        $this->rewriteDrafts();
        $this->rewriteFeaturedHotels();
        $this->retireFeaturedPlan();

        $this->replaceAdminListingsView(withHotels: false);
        $this->renameTables(toArchive: true);
    }

    public function down(): void
    {
        $this->renameTables(toArchive: false);
        $this->replaceAdminListingsView(withHotels: true);

        $this->repointMorphs(reverse: true);
        $this->restoreReviews();
        $this->restoreSavedSearches();
        $this->restoreDrafts();
        $this->restoreFeaturedHotels();
        $this->restoreFeaturedPlan();

        $ids = DB::table('archive_hotel_properties')->pluck('property_id');
        DB::table('properties')->whereIn('id', $ids)->delete();

        Schema::dropIfExists('archive_hotel_reviews');
        Schema::dropIfExists('archive_hotel_snapshots');
        Schema::dropIfExists('archive_hotel_properties');
    }

    private function mappingTables(): void
    {
        Schema::create('archive_hotel_properties', function (Blueprint $table) {
            $table->unsignedBigInteger('hotel_id')->primary();
            $table->unsignedBigInteger('property_id')->unique();
            $table->string('hotel_slug');
            $table->string('property_slug');
            $table->index('hotel_slug');
        });

        Schema::create('archive_hotel_snapshots', function (Blueprint $table) {
            $table->string('subject')->primary();
            $table->jsonb('payload');
        });
    }

    private function copyHotels(): void
    {
        /** @var array<string, true> $taken */
        $taken = DB::table('properties')->pluck('slug')->mapWithKeys(fn (mixed $slug): array => [(string) $slug => true])->all();

        DB::table('hotels')->orderBy('id')->chunkById(100, function ($hotels) use (&$taken): void {
            foreach ($hotels as $hotel) {
                $this->copyHotel($hotel, $taken);
            }
        });
    }

    /**
     * @param  array<string, true>  $taken
     */
    private function copyHotel(object $hotel, array &$taken): void
    {
        $slug = $this->uniqueSlug((string) $hotel->slug, $taken);
        $price = $hotel->price_from ?? DB::table('room_types')->where('hotel_id', $hotel->id)->min('base_price');
        $price = $price === null ? 0 : round((float) $price, 2);

        $propertyId = DB::table('properties')->insertGetId([
            'user_id' => $hotel->user_id,
            'business_profile_id' => $hotel->business_profile_id,
            'status' => $hotel->status,
            'slug' => $slug,
            'title' => $this->jsonValue($hotel->title),
            'description' => $this->jsonValue($hotel->description),
            'location_id' => $hotel->location_id,
            'lat' => $hotel->lat,
            'lng' => $hotel->lng,
            'address' => $this->jsonValue($hotel->address),
            'views_count' => $hotel->views_count,
            'published_at' => $hotel->published_at,
            'expires_at' => $hotel->expires_at,
            'rejection_reason' => $hotel->rejection_reason,
            'moderated_by' => $hotel->moderated_by,
            'moderated_at' => $hotel->moderated_at,
            'risk_score' => $hotel->risk_score,
            'promotion_level' => $hotel->promotion_level,
            'promoted_until' => $hotel->promoted_until,
            'bumped_at' => $hotel->bumped_at,
            'created_at' => $hotel->created_at,
            'updated_at' => $hotel->updated_at,
            'deleted_at' => $hotel->deleted_at,
            'deal' => 'daily_rent',
            'type' => 'hotel',
            'price' => $price,
            'currency' => 'GEL',
            'price_gel' => $price,
            'rooms' => $hotel->rooms_count,
            'listed_by' => 'owner',
            'distance_to_sea_m' => $hotel->distance_to_sea_m,
            'contact_phone' => $hotel->contact_phone,
        ]);

        DB::statement(
            'update properties set embedding = (select embedding from hotels where id = ?) where id = ?',
            [$hotel->id, $propertyId],
        );

        DB::table('archive_hotel_properties')->insert([
            'hotel_id' => $hotel->id,
            'property_id' => $propertyId,
            'hotel_slug' => $hotel->slug,
            'property_slug' => $slug,
        ]);
    }

    private function jsonValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_string($value) ? $value : json_encode($value);
    }

    /**
     * @param  array<string, true>  $taken
     */
    private function uniqueSlug(string $slug, array &$taken): string
    {
        $candidate = $slug;
        $suffix = 2;

        while (isset($taken[$candidate])) {
            $candidate = $slug.'-'.$suffix;
            $suffix++;
        }

        $taken[$candidate] = true;

        return $candidate;
    }

    private function repointMorphs(bool $reverse): void
    {
        foreach (self::MORPHS as [$table, $typeColumn, $idColumn]) {
            if ($reverse) {
                DB::statement(
                    "update {$table} as row set {$typeColumn} = 'hotel', {$idColumn} = map.hotel_id from archive_hotel_properties as map where row.{$typeColumn} = 'property' and row.{$idColumn} = map.property_id",
                );
            } else {
                DB::statement(
                    "update {$table} as row set {$typeColumn} = 'property', {$idColumn} = map.property_id from archive_hotel_properties as map where row.{$typeColumn} = 'hotel' and row.{$idColumn} = map.hotel_id",
                );
            }
        }
    }

    private function archiveReviews(): void
    {
        DB::statement('create table archive_hotel_reviews as table reviews with no data');
        DB::statement("insert into archive_hotel_reviews select * from reviews where reviewable_type = 'hotel'");
        DB::statement("delete from reviews where reviewable_type = 'hotel'");
    }

    private function restoreReviews(): void
    {
        if (! Schema::hasTable('archive_hotel_reviews')) {
            return;
        }

        DB::statement('insert into reviews select * from archive_hotel_reviews');
    }

    private function snapshotSavedSearches(): void
    {
        $rows = DB::table('saved_searches')->where('module', 'hotels')->get(['id', 'module', 'filters']);

        $this->snapshot('saved_searches', $rows->map(fn (object $row): array => [
            'id' => $row->id,
            'module' => $row->module,
            'filters' => $this->decode($row->filters),
        ])->all());
    }

    private function rewriteSavedSearches(): void
    {
        foreach ($this->snapshotPayload('saved_searches') as $row) {
            $filters = is_array($row['filters']) ? $row['filters'] : [];
            $filters['type'] = ['hotel'];

            DB::table('saved_searches')->where('id', $row['id'])->update([
                'module' => 'properties',
                'filters' => json_encode($filters),
            ]);
        }
    }

    private function restoreSavedSearches(): void
    {
        foreach ($this->snapshotPayload('saved_searches') as $row) {
            DB::table('saved_searches')->where('id', $row['id'])->update([
                'module' => $row['module'],
                'filters' => json_encode($row['filters'] ?? []),
            ]);
        }
    }

    private function snapshotDrafts(): void
    {
        $rows = DB::table('listing_drafts')->where('listing_type', 'hotel')->get(['id', 'listing_type', 'listing_id', 'data']);

        $this->snapshot('drafts', $rows->map(fn (object $row): array => [
            'id' => $row->id,
            'listing_type' => $row->listing_type,
            'listing_id' => $row->listing_id,
            'data' => $this->decode($row->data),
        ])->all());
    }

    private function rewriteDrafts(): void
    {
        $map = DB::table('archive_hotel_properties')->pluck('property_id', 'hotel_id');

        foreach ($this->snapshotPayload('drafts') as $row) {
            $listingId = $row['listing_id'];

            DB::table('listing_drafts')->where('id', $row['id'])->update([
                'listing_type' => 'property',
                'listing_id' => $listingId === null ? null : ($map[(int) $listingId] ?? null),
                'data' => json_encode($this->propertyDraft(is_array($row['data']) ? $row['data'] : [])),
            ]);
        }
    }

    private function restoreDrafts(): void
    {
        foreach ($this->snapshotPayload('drafts') as $row) {
            DB::table('listing_drafts')->where('id', $row['id'])->update([
                'listing_type' => $row['listing_type'],
                'listing_id' => $row['listing_id'],
                'data' => json_encode($row['data'] ?? []),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function propertyDraft(array $data): array
    {
        $price = $data['room']['base_price'] ?? '';

        return [
            'title' => $data['title'] ?? ['ka' => '', 'en' => '', 'ru' => ''],
            'description' => $data['description'] ?? ['ka' => '', 'en' => '', 'ru' => ''],
            'location_id' => $data['location_id'] ?? '',
            'address' => $data['address'] ?? '',
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'photos' => $data['photos'] ?? [],
            'contact_name' => $data['contact_name'] ?? '',
            'contact_phone' => $data['contact_phone'] ?? '',
            'deal' => 'daily_rent',
            'property_type' => 'hotel',
            'area_m2' => '',
            'land_area_m2' => '',
            'rooms' => isset($data['rooms_count']) && $data['rooms_count'] !== '' ? (string) $data['rooms_count'] : '',
            'bedrooms' => '',
            'bathrooms' => '',
            'floor' => '',
            'total_floors' => '',
            'condition' => '',
            'building_status' => '',
            'cadastral_code' => '',
            'listed_by' => 'owner',
            'features' => array_values(is_array($data['amenities'] ?? null) ? $data['amenities'] : []),
            'price' => (string) $price,
            'currency' => 'GEL',
        ];
    }

    private function snapshotFeaturedHotels(): void
    {
        $row = DB::table('settings')->where('key', 'homepage.featured_hotels')->first();

        $this->snapshot('featured_hotels', $row === null ? null : $this->decode($row->value));
    }

    private function rewriteFeaturedHotels(): void
    {
        $ids = $this->snapshotPayload('featured_hotels');

        if (! is_array($ids)) {
            return;
        }

        $map = DB::table('archive_hotel_properties')->pluck('property_id', 'hotel_id');
        $rewritten = [];

        foreach ($ids as $id) {
            $propertyId = $map[(int) $id] ?? null;

            if ($propertyId !== null) {
                $rewritten[] = (int) $propertyId;
            }
        }

        DB::table('settings')->where('key', 'homepage.featured_hotels')->update([
            'value' => json_encode($rewritten),
        ]);
    }

    private function restoreFeaturedHotels(): void
    {
        $payload = $this->snapshotPayload('featured_hotels');

        if (! is_array($payload)) {
            return;
        }

        DB::table('settings')->updateOrInsert(
            ['key' => 'homepage.featured_hotels'],
            ['value' => json_encode($payload), 'updated_at' => now()],
        );
    }

    private function snapshotFeaturedPlan(): void
    {
        $plan = DB::table('price_plans')->where('code', 'featured-hotel-30')->first(['is_active', 'description']);

        $this->snapshot('featured_plan', $plan === null ? null : [
            'is_active' => (bool) $plan->is_active,
            'description' => $this->decode($plan->description),
        ]);
    }

    private function retireFeaturedPlan(): void
    {
        DB::table('price_plans')->where('code', 'featured-hotel-30')->update([
            'is_active' => false,
            'description' => json_encode([
                'ka' => 'ეს გეგმა აღარ იყიდება. სასტუმროები უძრავი ქონების განცხადებებია.',
                'en' => 'This plan is no longer sold. Hotels are property listings.',
                'ru' => 'Этот тариф больше не продаётся. Отели — это объявления недвижимости.',
            ]),
        ]);
    }

    private function restoreFeaturedPlan(): void
    {
        $payload = $this->snapshotPayload('featured_plan');

        if (! is_array($payload)) {
            return;
        }

        DB::table('price_plans')->where('code', 'featured-hotel-30')->update([
            'is_active' => (bool) ($payload['is_active'] ?? false),
            'description' => json_encode($payload['description']),
        ]);
    }

    private function snapshot(string $subject, mixed $payload): void
    {
        DB::table('archive_hotel_snapshots')->insert([
            'subject' => $subject,
            'payload' => json_encode($payload),
        ]);
    }

    private function snapshotPayload(string $subject): mixed
    {
        $payload = DB::table('archive_hotel_snapshots')->where('subject', $subject)->value('payload');

        return $this->decode($payload);
    }

    private function decode(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return json_decode($value, true);
    }

    private function renameTables(bool $toArchive): void
    {
        $pairs = $toArchive ? self::TABLES : array_reverse(self::TABLES);

        foreach ($pairs as [$live, $archive]) {
            Schema::rename($toArchive ? $live : $archive, $toArchive ? $archive : $live);
        }

        // PostgreSQL keeps the index name when the table is renamed.
        DB::statement($toArchive
            ? 'alter index hotels_search_index rename to archive_hotels_search_index'
            : 'alter index archive_hotels_search_index rename to hotels_search_index');
    }

    private function replaceAdminListingsView(bool $withHotels): void
    {
        DB::statement('drop view if exists admin_listings');

        $tables = [
            'property' => 'properties',
            'classified' => 'classifieds',
            'vacancy' => 'vacancies',
            'place' => 'places',
            'article' => 'articles',
            'event' => 'events',
        ];

        if ($withHotels) {
            $tables = ['hotel' => 'hotels'] + $tables;
        }

        $selects = collect($tables)->map(fn (string $table, string $type) => <<<SQL
            SELECT '{$type}:' || id AS key, '{$type}'::varchar AS listing_type, id AS listing_id, user_id,
                   title, status, risk_score, views_count, published_at, created_at, updated_at
            FROM {$table} WHERE deleted_at IS NULL
            SQL);

        DB::statement('create view admin_listings as '.$selects->implode(' union all '));
    }
};
