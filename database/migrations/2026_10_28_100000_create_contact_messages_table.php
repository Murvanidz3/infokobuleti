<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6: messages sent through the public contact form (read in the admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->string('topic', 24)->default('general');
            $table->string('name', 120);
            $table->string('email', 190)->nullable();
            $table->string('phone', 32)->nullable();
            $table->text('message');
            $table->string('locale', 2)->default('ka');
            $table->string('ip', 45)->nullable();
            $table->timestamp('handled_at')->nullable()->index();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
