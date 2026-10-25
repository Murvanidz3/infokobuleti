<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
            $table->string('type', 16)->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->jsonb('description')->nullable();
            $table->string('phone', 16)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->jsonb('socials')->nullable();
            $table->jsonb('address')->nullable();
            $table->foreignId('location_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('verified_at')->nullable()->index();
            $table->decimal('rating_avg', 3, 1)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 16);
            $table->string('group', 32)->nullable();
            $table->jsonb('name');
            $table->string('slug');
            $table->string('icon', 32)->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_filterable')->default(true);
            $table->timestamps();

            $table->unique(['scope', 'slug']);
        });

        Schema::create('amenityables', function (Blueprint $table) {
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->morphs('amenityable');

            $table->primary(['amenity_id', 'amenityable_type', 'amenityable_id']);
        });
    }
};
