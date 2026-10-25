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
        Schema::create('properties', function (Blueprint $table) {
            ListingSchema::columns($table, promotable: true, embedding: true);

            $table->string('deal', 16);
            $table->string('type', 24);
            $table->decimal('price', 14, 2);
            $table->string('currency', 3)->default('GEL');
            $table->decimal('price_gel', 14, 2)->nullable()->index();
            $table->decimal('area_m2', 10, 2)->nullable()->index();
            $table->decimal('price_per_m2', 14, 2)->nullable()->storedAs('round(price / nullif(area_m2, 0), 2)');
            $table->decimal('land_area_m2', 12, 2)->nullable();
            $table->unsignedSmallInteger('rooms')->nullable()->index();
            $table->unsignedSmallInteger('bedrooms')->nullable();
            $table->unsignedSmallInteger('bathrooms')->nullable();
            $table->smallInteger('floor')->nullable()->index();
            $table->unsignedSmallInteger('total_floors')->nullable();
            $table->string('condition', 24)->nullable();
            $table->string('building_status', 24)->nullable()->index();
            $table->string('cadastral_code', 32)->nullable();
            $table->string('listed_by', 16)->default('owner')->index();
            $table->unsignedInteger('distance_to_sea_m')->nullable()->index();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 16)->nullable();

            $table->index(['deal', 'type']);
        });

        ListingSchema::indexes('properties', promotable: true);
        DB::statement('ALTER TABLE properties ADD CONSTRAINT properties_price_positive CHECK (price >= 0)');
    }
};
