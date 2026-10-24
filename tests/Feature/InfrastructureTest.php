<?php

use Illuminate\Support\Facades\DB;

it('runs on PostgreSQL with the pgvector extension', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    $vector = DB::selectOne("select extversion from pg_extension where extname = 'vector'");

    expect($vector)->not->toBeNull();
});

it('uses the Tbilisi timezone and Georgian as the default locale', function () {
    expect(config('app.timezone'))->toBe('Asia/Tbilisi')
        ->and(config('app.locale'))->toBe('ka')
        ->and(config('app.fallback_locale'))->toBe('en');
});
