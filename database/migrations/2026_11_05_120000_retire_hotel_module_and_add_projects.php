<?php

use App\Support\Database\ListingSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retires the hotel booking module (listings become property_type=hotel, deal=daily_rent),
 * folds country houses into private houses, drops the seller nearby checklist, and adds
 * construction projects. Hotel rows, rooms and booking requests are snapshotted so down()
 * can put the module back.
 */
return new class extends Migration
{
    public function up(): void
    {
        $countryHouseIds = Schema::hasTable('properties')
            ? DB::table('properties')->where('type', 'country_house')->pluck('id')->all()
            : [];

        if (Schema::hasColumn('properties', 'nearby')) {
            Schema::table('properties', function (Blueprint $table): void {
                $table->dropColumn('nearby');
            });
        }

        DB::table('properties')->where('type', 'country_house')->update(['type' => 'house']);

        Schema::create('hotel_module_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('hotel_id')->nullable();
            $table->unsignedBigInteger('property_id')->nullable();
            $table->jsonb('payload');
            $table->timestamps();
        });

        DB::table('hotel_module_snapshots')->insert([
            'hotel_id' => null,
            'property_id' => null,
            'payload' => json_encode(['country_house_ids' => $countryHouseIds], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->migrateHotels();
        $this->dropHotelTables();
        $this->createProjects();
        $this->refreshAdminListingsView();
        $this->redirectHotelUrls();
    }

    public function down(): void
    {
        $this->restoreHotels();
        $this->refreshAdminListingsView(withHotels: true);

        Schema::dropIfExists('projects');

        DB::table('redirects')->whereIn('from_path', ['/hotels', '/en/hotels', '/ru/hotels'])->delete();

        $note = DB::table('hotel_module_snapshots')->whereNull('hotel_id')->value('payload');
        $ids = json_decode((string) $note, true)['country_house_ids'] ?? [];

        if ($ids !== []) {
            DB::table('properties')->whereIn('id', $ids)->update(['type' => 'country_house']);
        }

        Schema::dropIfExists('hotel_module_snapshots');

        if (! Schema::hasColumn('properties', 'nearby')) {
            Schema::table('properties', function (Blueprint $table): void {
                $table->jsonb('nearby')->nullable();
            });
        }
    }

    private function migrateHotels(): void
    {
        if (! Schema::hasTable('hotels')) {
            return;
        }

        foreach (DB::table('hotels')->orderBy('id')->get() as $hotel) {
            $price = $hotel->price_from ?? DB::table('room_types')->where('hotel_id', $hotel->id)->min('base_price') ?? 0;
            $propertyId = DB::table('properties')->insertGetId([
                'user_id' => $hotel->user_id,
                'business_profile_id' => $hotel->business_profile_id,
                'status' => $hotel->status,
                'slug' => $this->uniqueSlug((string) $hotel->slug),
                'title' => $hotel->title,
                'description' => $hotel->description,
                'location_id' => $hotel->location_id,
                'lat' => $hotel->lat,
                'lng' => $hotel->lng,
                'address' => $hotel->address,
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
                'distance_to_sea_m' => $hotel->distance_to_sea_m,
                'contact_phone' => $hotel->contact_phone,
                'listed_by' => 'owner',
            ]);

            DB::table('hotel_module_snapshots')->insert([
                'hotel_id' => $hotel->id,
                'property_id' => $propertyId,
                'payload' => json_encode($this->snapshot($hotel), JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->repoint('media', 'model_type', 'model_id', $hotel->id, $propertyId);
            $this->repoint('favorites', 'favoritable_type', 'favoritable_id', $hotel->id, $propertyId);
            $this->repoint('reports', 'reportable_type', 'reportable_id', $hotel->id, $propertyId);
            $this->repoint('daily_stats', 'statable_type', 'statable_id', $hotel->id, $propertyId);
            $this->repoint('promotions', 'promotable_type', 'promotable_id', $hotel->id, $propertyId);
            $this->repoint('conversations', 'subject_type', 'subject_id', $hotel->id, $propertyId);

            DB::table('reviews')->where('reviewable_type', 'hotel')->where('reviewable_id', $hotel->id)->delete();
            DB::table('amenityables')->where('amenityable_type', 'hotel')->where('amenityable_id', $hotel->id)->delete();
        }

        DB::table('saved_searches')->where('module', 'hotels')->orderBy('id')->each(function (object $search): void {
            $filters = json_decode((string) $search->filters, true) ?: [];
            $filters['type'] = ['hotel'];

            DB::table('saved_searches')->where('id', $search->id)->update([
                'module' => 'properties',
                'filters' => json_encode($filters, JSON_UNESCAPED_UNICODE),
            ]);
        });

        DB::table('listing_drafts')->where('listing_type', 'hotel')->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(object $hotel): array
    {
        $rooms = DB::table('room_types')->where('hotel_id', $hotel->id)->get();
        $roomIds = $rooms->pluck('id');

        return [
            'hotel' => (array) $hotel,
            'rooms' => $rooms->map(fn (object $row): array => (array) $row)->all(),
            'rules' => DB::table('room_type_price_rules')->whereIn('room_type_id', $roomIds)->get()->map(fn (object $row): array => (array) $row)->all(),
            'availability' => DB::table('room_availability')->whereIn('room_type_id', $roomIds)->get()->map(fn (object $row): array => (array) $row)->all(),
            'bookings' => DB::table('booking_requests')->where('hotel_id', $hotel->id)->get()->map(fn (object $row): array => (array) $row)->all(),
        ];
    }

    private function dropHotelTables(): void
    {
        DB::statement('DROP VIEW IF EXISTS admin_listings');
        Schema::dropIfExists('booking_requests');
        Schema::dropIfExists('room_availability');
        Schema::dropIfExists('room_type_price_rules');
        Schema::dropIfExists('room_types');
        Schema::dropIfExists('hotels');
    }

    private function createProjects(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            ListingSchema::columns($table, promotable: true, embedding: true);
            $table->string('developer', 160);
            $table->date('completion_on')->nullable()->index();
            $table->decimal('price_per_m2', 14, 2);
            $table->string('currency', 3)->default('GEL');
            $table->decimal('price_gel', 14, 2)->nullable()->index();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 16)->nullable();
            $table->unsignedInteger('distance_to_sea_m')->nullable()->index();
        });

        ListingSchema::indexes('projects', promotable: true);
        DB::statement('ALTER TABLE projects ADD CONSTRAINT projects_price_positive CHECK (price_per_m2 >= 0)');
    }

    private function redirectHotelUrls(): void
    {
        foreach (['/hotels', '/en/hotels', '/ru/hotels'] as $from) {
            $to = match ($from) {
                '/en/hotels' => '/en/real-estate/hotel',
                '/ru/hotels' => '/ru/real-estate/hotel',
                default => '/real-estate/hotel',
            };

            DB::table('redirects')->updateOrInsert(['from_path' => $from], [
                'to_url' => $to,
                'status_code' => 301,
                'hits' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function restoreHotels(): void
    {
        if (Schema::hasTable('hotels')) {
            return;
        }

        $this->recreateHotelSchema();

        foreach (DB::table('hotel_module_snapshots')->whereNotNull('hotel_id')->orderBy('hotel_id')->get() as $row) {
            $payload = json_decode((string) $row->payload, true);
            $hotel = $payload['hotel'];
            unset($hotel['embedding']);
            DB::table('hotels')->insert($hotel);

            foreach ($payload['rooms'] as $room) {
                DB::table('room_types')->insert($room);
            }

            foreach ($payload['rules'] as $rule) {
                DB::table('room_type_price_rules')->insert($rule);
            }

            foreach ($payload['availability'] as $day) {
                DB::table('room_availability')->insert($day);
            }

            foreach ($payload['bookings'] as $booking) {
                DB::table('booking_requests')->insert($booking);
            }

            if ($row->property_id !== null) {
                $this->repoint('media', 'model_type', 'model_id', (int) $row->property_id, (int) $row->hotel_id, 'property', 'hotel');
                DB::table('properties')->where('id', $row->property_id)->delete();
            }
        }

        foreach (['hotels', 'room_types', 'room_type_price_rules', 'room_availability', 'booking_requests'] as $table) {
            DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), GREATEST((SELECT COALESCE(MAX(id), 1) FROM {$table}), 1))");
        }
    }

    private function recreateHotelSchema(): void
    {
        Schema::create('hotels', function (Blueprint $table): void {
            ListingSchema::columns($table, promotable: true, embedding: true);
            $table->string('type', 16)->index();
            $table->unsignedSmallInteger('stars')->nullable()->index();
            $table->unsignedInteger('distance_to_sea_m')->nullable()->index();
            $table->time('check_in_from')->nullable();
            $table->time('check_out_until')->nullable();
            $table->decimal('price_from', 10, 2)->nullable()->index();
            $table->unsignedSmallInteger('rooms_count')->nullable();
            $table->unsignedSmallInteger('season_start_month')->nullable();
            $table->unsignedSmallInteger('season_end_month')->nullable();
            $table->string('contact_phone', 16)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('website')->nullable();
            $table->decimal('rating_avg', 3, 1)->nullable()->index();
            $table->unsignedInteger('reviews_count')->default(0);
        });

        ListingSchema::indexes('hotels', promotable: true);
        DB::statement('ALTER TABLE hotels ADD CONSTRAINT hotels_stars_range CHECK (stars BETWEEN 1 AND 5)');
        DB::statement('ALTER TABLE hotels ADD CONSTRAINT hotels_season_months CHECK (season_start_month BETWEEN 1 AND 12 AND season_end_month BETWEEN 1 AND 12)');

        Schema::create('room_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hotel_id')->index()->constrained()->cascadeOnDelete();
            $table->jsonb('name');
            $table->jsonb('description')->nullable();
            $table->unsignedSmallInteger('capacity_adults')->default(2);
            $table->unsignedSmallInteger('capacity_children')->default(0);
            $table->jsonb('beds')->nullable();
            $table->unsignedSmallInteger('size_m2')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->unsignedSmallInteger('units')->default(1);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('room_type_price_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->jsonb('name')->nullable();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->decimal('price', 10, 2);
            $table->unsignedSmallInteger('min_nights')->default(1);
            $table->timestamps();

            $table->index(['room_type_id', 'starts_on', 'ends_on']);
        });

        DB::statement('ALTER TABLE room_type_price_rules ADD CONSTRAINT room_type_price_rules_dates CHECK (ends_on >= starts_on)');

        Schema::create('room_availability', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_closed')->default(false);
            $table->decimal('price_override', 10, 2)->nullable();
            $table->unsignedSmallInteger('units_available')->nullable();
            $table->timestamps();
            $table->unique(['room_type_id', 'date']);
        });

        Schema::create('booking_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->date('check_in')->index();
            $table->date('check_out');
            $table->unsignedSmallInteger('adults')->default(2);
            $table->unsignedSmallInteger('children')->default(0);
            $table->string('contact_name');
            $table->string('contact_phone', 16);
            $table->string('contact_email')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 16)->default('pending');
            $table->text('owner_note')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->string('locale', 2)->default('ka');
            $table->timestamps();

            $table->index(['hotel_id', 'status']);
        });

        DB::statement('ALTER TABLE booking_requests ADD CONSTRAINT booking_requests_dates CHECK (check_out > check_in)');
    }

    private function refreshAdminListingsView(bool $withHotels = false): void
    {
        $tables = [
            'property' => 'properties',
            'project' => 'projects',
            'classified' => 'classifieds',
            'vacancy' => 'vacancies',
            'place' => 'places',
            'article' => 'articles',
            'event' => 'events',
        ];

        if ($withHotels) {
            $tables = ['hotel' => 'hotels'] + $tables;
            unset($tables['project']);
        }

        $selects = collect($tables)->map(fn (string $table, string $type): string => <<<SQL
            SELECT '{$type}:' || id AS key, '{$type}'::varchar AS listing_type, id AS listing_id, user_id,
                   title, status, risk_score, views_count, published_at, created_at, updated_at
            FROM {$table} WHERE deleted_at IS NULL
            SQL);

        DB::statement('DROP VIEW IF EXISTS admin_listings');
        DB::statement('CREATE VIEW admin_listings AS '.$selects->implode(' UNION ALL '));
    }

    private function uniqueSlug(string $slug): string
    {
        $candidate = $slug;
        $suffix = 2;

        while (DB::table('properties')->where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function repoint(string $table, string $typeColumn, string $idColumn, int $fromId, int $toId, string $fromType = 'hotel', string $toType = 'property'): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        DB::table($table)->where($typeColumn, $fromType)->where($idColumn, $fromId)->update([
            $typeColumn => $toType,
            $idColumn => $toId,
        ]);
    }
};
