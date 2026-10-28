<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5: static pages, banner slots, the price list, site settings, and one read-only view over
 * every listing module for the admin (moderation queue, dashboard).
 */
return new class extends Migration
{
    /** morph alias => table */
    private const array LISTING_TABLES = [
        'hotel' => 'hotels',
        'property' => 'properties',
        'classified' => 'classifieds',
        'vacancy' => 'vacancies',
        'place' => 'places',
        'article' => 'articles',
        'event' => 'events',
    ];

    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->jsonb('title');
            $table->jsonb('body')->nullable();
            $table->jsonb('meta_description')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('slot', 32)->index();
            $table->jsonb('title');
            $table->string('image_path')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();

            $table->index(['slot', 'is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('price_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 48)->unique();
            $table->string('product', 24)->index();
            $table->string('module', 16)->nullable()->index();
            $table->jsonb('name');
            $table->jsonb('description')->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedSmallInteger('listings_limit')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('GEL');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->jsonb('value')->nullable();
            $table->timestamps();
        });

        $selects = collect(self::LISTING_TABLES)->map(fn (string $table, string $type) => <<<SQL
            SELECT '{$type}:' || id AS key, '{$type}'::varchar AS listing_type, id AS listing_id, user_id,
                   title, status, risk_score, views_count, published_at, created_at, updated_at
            FROM {$table} WHERE deleted_at IS NULL
            SQL);

        DB::statement('CREATE VIEW admin_listings AS '.$selects->implode(' UNION ALL '));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS admin_listings');

        Schema::dropIfExists('settings');
        Schema::dropIfExists('price_plans');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('pages');
    }
};
