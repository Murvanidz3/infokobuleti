<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The public contact address is contact@infokobuleti.com (owner decision, Oct 27 2026). Replaces the
 * old default where the admin settings still hold it; a custom address set by staff is kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')
            ->where('key', 'contacts.email')
            ->whereRaw('value = to_jsonb(?::text)', ['info@infokobuleti.com'])
            ->update(['value' => DB::raw("to_jsonb('contact@infokobuleti.com'::text)")]);
        Cache::forget('settings.all');
    }

    public function down(): void
    {
        // Nothing to undo: the old address is not used any more.
    }
};
