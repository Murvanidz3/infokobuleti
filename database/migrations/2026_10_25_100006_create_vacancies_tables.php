<?php

use App\Support\Database\ListingSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancies', function (Blueprint $table) {
            ListingSchema::columns($table, promotable: true, embedding: true);

            $table->foreignId('category_id')->index()->constrained()->restrictOnDelete();
            $table->string('employment_type', 16)->index();
            $table->unsignedInteger('salary_min')->nullable()->index();
            $table->unsignedInteger('salary_max')->nullable()->index();
            $table->string('salary_currency', 3)->default('GEL');
            $table->string('salary_period', 16)->default('month');
            $table->string('experience', 16)->nullable();
            $table->jsonb('languages')->default('[]');
            $table->jsonb('benefits')->default('[]');
            $table->date('deadline')->nullable()->index();
            $table->string('apply_method', 16)->default('site');
            $table->string('apply_phone', 16)->nullable();
            $table->string('apply_email')->nullable();
        });

        ListingSchema::indexes('vacancies', promotable: true);
        DB::statement('CREATE INDEX vacancies_languages_index ON vacancies USING gin (languages jsonb_path_ops)');
        DB::statement('CREATE INDEX vacancies_benefits_index ON vacancies USING gin (benefits jsonb_path_ops)');
        DB::statement('ALTER TABLE vacancies ADD CONSTRAINT vacancies_salary_range CHECK (salary_max IS NULL OR salary_min IS NULL OR salary_max >= salary_min)');

        Schema::create('vacancy_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacancy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
            $table->text('cover_note')->nullable();
            $table->string('contact_phone', 16)->nullable();
            $table->string('status', 16)->default('new');
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();

            $table->unique(['vacancy_id', 'user_id']);
            $table->index(['vacancy_id', 'status']);
        });
    }
};
