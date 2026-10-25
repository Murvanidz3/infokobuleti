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
        Schema::create('places', function (Blueprint $table) {
            ListingSchema::columns($table, expires: false, embedding: true);

            $table->string('type', 16)->index();
            $table->jsonb('content')->nullable();
            $table->jsonb('opening_hours')->nullable();
            $table->string('phone', 16)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->unsignedSmallInteger('price_level')->nullable();
            $table->decimal('rating_avg', 3, 1)->nullable()->index();
            $table->unsignedInteger('reviews_count')->default(0);
        });

        ListingSchema::indexes('places');
        DB::statement('ALTER TABLE places ADD CONSTRAINT places_price_level_range CHECK (price_level BETWEEN 1 AND 4)');

        Schema::create('articles', function (Blueprint $table) {
            ListingSchema::columns($table, location: false, expires: false, description: false);

            $table->foreignId('category_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->jsonb('excerpt')->nullable();
            $table->jsonb('body')->nullable();
            $table->boolean('is_featured')->default(false)->index();
        });

        ListingSchema::indexes('articles', searchColumns: ['title', 'excerpt']);

        Schema::create('events', function (Blueprint $table) {
            ListingSchema::columns($table, expires: false);

            $table->foreignId('category_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->foreignId('place_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_all_day')->default(false);
            $table->jsonb('venue')->nullable();
            $table->string('organizer')->nullable();
            $table->string('ticket_url')->nullable();
            $table->boolean('is_free')->default(true);
            $table->decimal('price_from', 10, 2)->nullable();
        });

        ListingSchema::indexes('events');
        DB::statement('ALTER TABLE events ADD CONSTRAINT events_dates CHECK (ends_at IS NULL OR ends_at >= starts_at)');
    }
};
