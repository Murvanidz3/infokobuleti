<?php

use App\Support\Database\ListingSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
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

        Schema::create('room_types', function (Blueprint $table) {
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

        Schema::create('room_type_price_rules', function (Blueprint $table) {
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

        // Exceptions only: a missing row means the date is open at the base or seasonal price.
        Schema::create('room_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_closed')->default(false);
            $table->decimal('price_override', 10, 2)->nullable();
            $table->unsignedSmallInteger('units_available')->nullable();
            $table->timestamps();

            $table->unique(['room_type_id', 'date']);
        });

        Schema::create('booking_requests', function (Blueprint $table) {
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
};
