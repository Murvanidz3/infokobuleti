<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 8: orders (what is bought), the wallet ledger, business package subscriptions, the bank
 * message log, and the promotion columns for auto-bumps and expiry reminders.
 * Only adds tables and columns; nothing existing is changed or removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('balance', 10, 2)->default(0);
        });

        DB::statement('ALTER TABLE users ADD CONSTRAINT users_balance_not_negative CHECK (balance >= 0)');

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 24)->nullable()->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('price_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product', 24)->index();
            $table->nullableMorphs('purchasable');
            $table->jsonb('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('GEL');
            $table->string('method', 12)->nullable();
            $table->string('status', 24)->default('pending')->index();
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->jsonb('options')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        DB::statement('CREATE SEQUENCE IF NOT EXISTS invoice_number_seq START 1');

        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->index()->constrained()->cascadeOnDelete();
            $table->string('gateway', 16);
            $table->string('type', 24)->index();
            $table->string('status', 24)->nullable();
            $table->boolean('signature_valid')->nullable();
            $table->jsonb('payload')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->string('type', 16);
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });

        // One credit and one debit per order at most: a repeated callback can never pay twice.
        DB::statement("CREATE UNIQUE INDEX wallet_transactions_order_type_unique ON wallet_transactions (order_id, type) WHERE order_id IS NOT NULL AND type IN ('top_up', 'purchase')");

        Schema::create('business_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('price_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('listings_limit')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expiry_notified_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'ends_at']);
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->timestamp('next_run_at')->nullable()->index();
            $table->timestamp('expiry_notified_at')->nullable();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('redirect_url', 1024)->nullable();
        });

        // Cached end of the owner's business package: verified badge and logo on cards without a join.
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->timestamp('package_until')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn('package_until');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('redirect_url');
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
            $table->dropColumn(['next_run_at', 'expiry_notified_at']);
        });

        Schema::dropIfExists('business_subscriptions');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('payment_events');
        DB::statement('DROP SEQUENCE IF EXISTS invoice_number_seq');
        Schema::dropIfExists('orders');

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_balance_not_negative');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('balance');
        });
    }
};
