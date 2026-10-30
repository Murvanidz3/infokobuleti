<?php

use App\Support\Listings\PropertyLegacyValues;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->jsonb('nearby')->nullable();
            $table->index('condition');
        });

        // Price per m² uses the built area, and the plot area when there is no building (land).
        DB::statement('ALTER TABLE properties DROP COLUMN price_per_m2');
        DB::statement('ALTER TABLE properties ADD COLUMN price_per_m2 numeric(14, 2) GENERATED ALWAYS AS (round(price / nullif(coalesce(area_m2, land_area_m2), 0), 2)) STORED');

        PropertyLegacyValues::apply();
    }

    public function down(): void
    {
        PropertyLegacyValues::rollback();

        DB::statement('ALTER TABLE properties DROP COLUMN price_per_m2');
        DB::statement('ALTER TABLE properties ADD COLUMN price_per_m2 numeric(14, 2) GENERATED ALWAYS AS (round(price / nullif(area_m2, 0), 2)) STORED');

        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['condition']);
            $table->dropColumn('nearby');
        });
    }
};
