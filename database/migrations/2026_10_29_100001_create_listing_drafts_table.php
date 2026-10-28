<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7: autosaved state of the add-listing wizard. A draft becomes (or updates) a real listing
 * only when it is submitted to moderation, so edits never reach a live listing unmoderated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('listing_type', 16)->nullable();
            $table->unsignedBigInteger('listing_id')->nullable();
            $table->unsignedSmallInteger('step')->default(1);
            $table->jsonb('data')->default('{}');
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
            $table->index(['listing_type', 'listing_id']);
        });

        // One draft per listing being edited (new-listing drafts have no listing_id).
        DB::statement('CREATE UNIQUE INDEX listing_drafts_editing_unique ON listing_drafts (user_id, listing_type, listing_id) WHERE listing_id IS NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_drafts');
    }
};
