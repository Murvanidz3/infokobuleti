<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 9: web push subscriptions, Telegram links, the "owner verified" property badge and the
 * saved-search alert log. Only adds tables and columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
            $table->string('endpoint', 500)->unique();
            $table->string('public_key');
            $table->string('auth_token');
            $table->string('content_encoding', 16)->default('aes128gcm');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('telegram_chat_id', 32)->nullable()->unique();
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->timestamp('owner_verified_at')->nullable();
            $table->foreignId('owner_verified_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('saved_search_hits', function (Blueprint $table) {
            $table->foreignId('saved_search_id')->constrained()->cascadeOnDelete();
            $table->morphs('listing');
            $table->timestamp('notified_at')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['saved_search_id', 'listing_type', 'listing_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_search_hits');

        Schema::table('properties', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_verified_by');
            $table->dropColumn('owner_verified_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('telegram_chat_id');
        });

        Schema::dropIfExists('push_subscriptions');
    }
};
