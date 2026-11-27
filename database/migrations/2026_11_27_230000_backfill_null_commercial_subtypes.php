<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Commercial listings that existed before commercial_subtype was added are stored as NULL
 * and fail the wizard's required check. Assign them "other" and remember those ids in
 * property_taxonomy_snapshots (type commercial) so down() can clear only those rows.
 *
 * Former hotel_business rows are already in that table with type hotel_business. This
 * migration does not insert over them and down() deletes only type commercial.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $legacy = DB::table('properties')
                ->where('type', 'commercial')
                ->whereNull('commercial_subtype')
                ->whereNotExists(function (Builder $query): void {
                    $query->selectRaw('1')
                        ->from('property_taxonomy_snapshots')
                        ->whereColumn('property_taxonomy_snapshots.property_id', 'properties.id');
                })
                ->select(['id', 'type']);

            DB::table('property_taxonomy_snapshots')->insertUsing(['property_id', 'type'], $legacy);

            DB::table('properties')
                ->where('type', 'commercial')
                ->whereNull('commercial_subtype')
                ->whereExists(function (Builder $query): void {
                    $query->selectRaw('1')
                        ->from('property_taxonomy_snapshots')
                        ->whereColumn('property_taxonomy_snapshots.property_id', 'properties.id')
                        ->where('property_taxonomy_snapshots.type', 'commercial');
                })
                ->update(['commercial_subtype' => 'other']);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('properties')
                ->where('commercial_subtype', 'other')
                ->whereIn('id', function (Builder $query): void {
                    $query->select('property_id')
                        ->from('property_taxonomy_snapshots')
                        ->where('type', 'commercial');
                })
                ->update(['commercial_subtype' => null]);

            DB::table('property_taxonomy_snapshots')->where('type', 'commercial')->delete();
        });
    }
};
