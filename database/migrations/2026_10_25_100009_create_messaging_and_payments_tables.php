<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('subject');
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->primary(['conversation_id', 'user_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->nullableMorphs('payable');
            $table->string('gateway', 16);
            $table->string('gateway_order_id')->nullable()->unique();
            $table->uuid('idempotency_key')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('GEL');
            $table->string('status', 24)->default('pending')->index();
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->jsonb('payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->morphs('promotable');
            $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->string('type', 16);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'ends_at']);
        });
    }
};
