<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4: staff two-factor auth, onboarding state, SMS login codes, the SMS delivery log and the
 * list of signed-in devices. Additive only: existing rows are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->timestamp('onboarded_at')->nullable()->after('account_type');
        });

        // Accounts created before onboarding existed already have a name: treat them as onboarded.
        DB::table('users')->whereNotNull('name')->update(['onboarded_at' => DB::raw('created_at')]);

        Schema::create('phone_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 16);
            $table->string('purpose', 16);
            $table->foreignId('user_id')->nullable()->index()->constrained()->cascadeOnDelete();
            $table->char('code_hash', 64);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('locale', 2);
            $table->timestamps();

            $table->index(['phone', 'purpose', 'created_at']);
        });

        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 16)->index();
            $table->string('purpose', 32);
            $table->string('driver', 16);
            $table->string('status', 16)->index();
            $table->string('provider_message_id')->nullable()->index();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
            $table->ulid('device_id')->unique();
            $table->string('session_id')->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('remembered')->default(false);
            $table->timestamp('last_active_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('phone_verifications');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'onboarded_at']);
        });
    }
};
