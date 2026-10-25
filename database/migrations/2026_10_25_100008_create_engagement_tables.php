<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('favoritable');
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'favoritable_type', 'favoritable_id']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
            $table->morphs('reviewable');
            $table->unsignedSmallInteger('rating');
            $table->text('body')->nullable();
            $table->string('status', 16)->default('pending')->index();
            $table->text('owner_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->string('locale', 2)->default('ka');
            $table->foreignId('moderated_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'reviewable_type', 'reviewable_id']);
        });

        DB::statement('ALTER TABLE reviews ADD CONSTRAINT reviews_rating_range CHECK (rating BETWEEN 1 AND 10)');

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->morphs('reportable');
            $table->string('reason', 24);
            $table->text('comment')->nullable();
            $table->string('status', 16)->default('open')->index();
            $table->foreignId('handled_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();
        });

        Schema::create('daily_stats', function (Blueprint $table) {
            $table->id();
            $table->morphs('statable');
            $table->date('date')->index();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('phone_reveals')->default(0);
            $table->unsignedInteger('favorites')->default(0);
            $table->unsignedInteger('messages')->default(0);

            $table->unique(['statable_type', 'statable_id', 'date']);
        });

        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
            $table->string('module', 16);
            $table->string('name')->nullable();
            $table->jsonb('filters')->default('{}');
            $table->jsonb('channels')->default('["email"]');
            $table->string('frequency', 16)->default('daily');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->index(['module', 'is_active', 'frequency']);
        });

        DB::statement('CREATE INDEX saved_searches_filters_index ON saved_searches USING gin (filters jsonb_path_ops)');
    }
};
