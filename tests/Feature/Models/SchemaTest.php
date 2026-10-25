<?php

use App\Models\BookingRequest;
use App\Models\Review;
use App\Models\User;
use App\Support\Database\ListingSchema;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Runs a statement expected to fail inside a savepoint, so the test transaction stays usable.
 */
function expectQueryToFail(Closure $callback): void
{
    expect(fn () => DB::transaction($callback))->toThrow(QueryException::class);
}

it('creates every table of the data model', function (string $table) {
    expect(Schema::hasTable($table))->toBeTrue("Missing table {$table}");
})->with([
    'users', 'business_profiles', 'locations', 'categories', 'amenities', 'amenityables',
    'hotels', 'room_types', 'room_type_price_rules', 'room_availability', 'booking_requests',
    'properties', 'classifieds', 'vacancies', 'vacancy_applications', 'places', 'articles', 'events',
    'favorites', 'reviews', 'reports', 'daily_stats', 'saved_searches',
    'conversations', 'conversation_participants', 'messages', 'payments', 'promotions',
    'roles', 'permissions', 'model_has_roles', 'media', 'activity_log',
]);

it('adds a nullable pgvector embedding column with an HNSW index', function (string $table) {
    $type = DB::selectOne(
        'select format_type(atttypid, atttypmod) as type, attnotnull from pg_attribute where attrelid = ?::regclass and attname = ?',
        [$table, 'embedding'],
    );

    $index = DB::selectOne("select indexdef from pg_indexes where tablename = ? and indexdef like '%hnsw%'", [$table]);

    expect($type->type)->toBe('vector('.ListingSchema::EMBEDDING_DIMENSIONS.')')
        ->and($type->attnotnull)->toBeFalse()
        ->and($index?->indexdef)->toContain('vector_cosine_ops');
})->with(['hotels', 'properties', 'classifieds', 'vacancies', 'places']);

it('has a full-text index on every module', function (string $table) {
    $index = DB::selectOne('select indexdef from pg_indexes where tablename = ? and indexname = ?', [$table, "{$table}_search_index"]);

    expect($index?->indexdef)->toContain('to_tsvector');
})->with(['hotels', 'properties', 'classifieds', 'vacancies', 'places', 'articles', 'events']);

it('keeps phone and email unique only among active users', function () {
    $user = User::factory()->create(['phone' => '+995555123456', 'email' => 'nino@example.com']);

    expectQueryToFail(fn () => User::factory()->create(['phone' => '+995555123456']));

    $user->delete();

    expect(User::factory()->create(['phone' => '+995555123456', 'email' => 'nino@example.com'])->exists)->toBeTrue();
});

it('requires an email or a phone on every user', function () {
    expectQueryToFail(fn () => User::factory()->create(['email' => null, 'phone' => null]));
});

it('stores emails in lower case', function () {
    expect(User::factory()->create(['email' => ' Nino@Example.COM '])->email)->toBe('nino@example.com');
});

it('enforces review ratings between 1 and 10', function () {
    expectQueryToFail(fn () => Review::factory()->create(['rating' => 11]));
});

it('rejects booking requests that end before they start', function () {
    expectQueryToFail(fn () => BookingRequest::factory()->create(['check_in' => '2027-07-10', 'check_out' => '2027-07-10']));
});
