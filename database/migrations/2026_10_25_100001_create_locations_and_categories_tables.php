<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->index()->constrained('locations')->nullOnDelete();
            $table->string('type', 24)->index();
            $table->jsonb('name');
            $table->string('slug')->unique();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->index()->constrained('categories')->nullOnDelete();
            $table->string('module', 24);
            $table->jsonb('name');
            $table->string('slug');
            $table->string('icon', 32)->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->jsonb('attribute_schema')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['module', 'slug']);
            $table->index(['module', 'parent_id', 'sort']);
        });
    }
};
