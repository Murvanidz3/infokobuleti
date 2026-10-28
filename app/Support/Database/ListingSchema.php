<?php

namespace App\Support\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

/**
 * Columns and indexes shared by every module table (hotels, properties, classifieds, vacancies,
 * places, events, articles).
 *
 * Existing migrations depend on the exact output of this class: change it only together with a new
 * migration that alters the affected tables.
 */
final class ListingSchema
{
    public const int EMBEDDING_DIMENSIONS = 1024;

    /** Fixed rather than read from config so the index expression never drifts from the migrations. */
    public const array LOCALES = ['ka', 'en', 'ru'];

    public static function columns(
        Blueprint $table,
        bool $location = true,
        bool $expires = true,
        bool $promotable = false,
        bool $embedding = false,
        bool $description = true,
    ): void {
        $table->id();
        $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
        $table->foreignId('business_profile_id')->nullable()->index()->constrained()->nullOnDelete();
        $table->string('status', 16)->default('draft');
        $table->string('slug')->unique();
        $table->jsonb('title');

        if ($description) {
            $table->jsonb('description')->nullable();
        }

        if ($location) {
            $table->foreignId('location_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->jsonb('address')->nullable();
            $table->index(['lat', 'lng']);
        }

        $table->unsignedInteger('views_count')->default(0);
        $table->timestamp('published_at')->nullable();

        if ($expires) {
            $table->timestamp('expires_at')->nullable()->index();
        }

        $table->text('rejection_reason')->nullable();
        $table->foreignId('moderated_by')->nullable()->index()->constrained('users')->nullOnDelete();
        $table->timestamp('moderated_at')->nullable();
        $table->unsignedSmallInteger('risk_score')->nullable();

        if ($promotable) {
            $table->unsignedSmallInteger('promotion_level')->default(0);
            $table->timestamp('promoted_until')->nullable()->index();
            $table->timestamp('bumped_at')->nullable();
        }

        if ($embedding) {
            $table->vector('embedding', self::EMBEDDING_DIMENSIONS)->nullable();
            $table->vectorIndex('embedding');
        }

        $table->timestamps();
        $table->softDeletes();
    }

    /**
     * Indexes Blueprint cannot express: sort-direction composites and the full-text GIN index.
     *
     * @param  list<string>  $searchColumns
     */
    public static function indexes(string $table, bool $promotable = false, array $searchColumns = ['title', 'description']): void
    {
        DB::statement("CREATE INDEX {$table}_status_published_index ON {$table} (status, published_at DESC)");

        if ($promotable) {
            DB::statement("CREATE INDEX {$table}_listing_order_index ON {$table} (status, promotion_level DESC, bumped_at DESC NULLS LAST, published_at DESC)");
        }

        DB::statement("CREATE INDEX {$table}_search_index ON {$table} USING gin (".self::searchVector($searchColumns).')');
    }

    /**
     * The tsvector expression behind the full-text index. Queries must use the identical expression
     * for PostgreSQL to use the index. "simple" because PostgreSQL ships no Georgian dictionary.
     *
     * @param  list<string>  $columns
     */
    public static function searchVector(array $columns = ['title', 'description']): string
    {
        $parts = [];

        foreach ($columns as $column) {
            foreach (self::LOCALES as $locale) {
                $parts[] = "coalesce({$column}->>'{$locale}', '')";
            }
        }

        return "to_tsvector('simple'::regconfig, ".implode(" || ' ' || ", $parts).')';
    }
}
