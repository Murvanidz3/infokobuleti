<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * pgvector is not a trusted extension: on a new server a superuser must create it first
 * (sudo -u postgres psql -d <db> -c "CREATE EXTENSION vector"). IF NOT EXISTS makes this a no-op then.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
    }
};
