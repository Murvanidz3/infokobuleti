<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the vacation and land/commercial columns and maps existing rows onto them.
 *
 * hotel_business becomes commercial with subtype hotel_business. The previous type is stored in
 * property_taxonomy_snapshots so down() can put only those rows back.
 *
 * A daily-rent hotel, apartment or house gets a vacation_type equal to its type. Rows copied from
 * the hotel archive then take stars, check-in, check-out, website, room count and the archive
 * subtype (cottage becomes house). Nothing is deleted and no column is dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_taxonomy_snapshots', function (Blueprint $table): void {
            $table->foreignId('property_id')->primary()->constrained('properties')->cascadeOnDelete();
            $table->string('type', 24);
        });

        Schema::table('properties', function (Blueprint $table): void {
            $table->string('purpose', 24)->nullable();
            $table->jsonb('utilities')->nullable();
            $table->string('commercial_subtype', 24)->nullable();
            $table->unsignedSmallInteger('guests')->nullable();
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->unsignedTinyInteger('stars')->nullable();
            $table->unsignedSmallInteger('rooms_count')->nullable();
            $table->string('vacation_type', 24)->nullable();
            $table->string('website')->nullable();
            $table->index('vacation_type');
            $table->index('commercial_subtype');
        });

        DB::statement('alter table properties add constraint properties_stars_check check (stars is null or (stars between 1 and 5))');

        DB::table('property_taxonomy_snapshots')->insertUsing(
            ['property_id', 'type'],
            DB::table('properties')->where('type', 'hotel_business')->select(['id', 'type']),
        );

        DB::table('properties')->where('type', 'hotel_business')->update([
            'type' => 'commercial',
            'commercial_subtype' => 'hotel_business',
        ]);

        DB::table('properties')
            ->where('deal', 'daily_rent')
            ->whereIn('type', ['hotel', 'apartment', 'house'])
            ->whereNull('vacation_type')
            ->update(['vacation_type' => DB::raw('type')]);

        if (Schema::hasTable('archive_hotels') && Schema::hasTable('archive_hotel_properties')) {
            DB::statement(<<<'SQL'
                update properties as properties
                set vacation_type = case archive_hotels.type
                        when 'cottage' then 'house'
                        when 'hotel' then 'hotel'
                        when 'guesthouse' then 'guesthouse'
                        when 'apartment' then 'apartment'
                        when 'hostel' then 'hostel'
                        else properties.vacation_type
                    end,
                    stars = archive_hotels.stars,
                    check_in = archive_hotels.check_in_from,
                    check_out = archive_hotels.check_out_until,
                    website = archive_hotels.website,
                    rooms_count = archive_hotels.rooms_count
                from archive_hotel_properties
                join archive_hotels on archive_hotels.id = archive_hotel_properties.hotel_id
                where properties.id = archive_hotel_properties.property_id
            SQL);
        }
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
            update properties
            set type = property_taxonomy_snapshots.type
            from property_taxonomy_snapshots
            where properties.id = property_taxonomy_snapshots.property_id
        SQL);

        Schema::dropIfExists('property_taxonomy_snapshots');

        DB::statement('alter table properties drop constraint if exists properties_stars_check');

        Schema::table('properties', function (Blueprint $table): void {
            $table->dropIndex(['vacation_type']);
            $table->dropIndex(['commercial_subtype']);
            $table->dropColumn([
                'purpose',
                'utilities',
                'commercial_subtype',
                'guests',
                'check_in',
                'check_out',
                'stars',
                'rooms_count',
                'vacation_type',
                'website',
            ]);
        });
    }
};
