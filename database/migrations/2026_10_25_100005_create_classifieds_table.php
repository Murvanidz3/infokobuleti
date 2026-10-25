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
        Schema::create('classifieds', function (Blueprint $table) {
            ListingSchema::columns($table, promotable: true, embedding: true);

            $table->foreignId('category_id')->index()->constrained()->restrictOnDelete();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->default('GEL');
            $table->decimal('price_gel', 12, 2)->nullable()->index();
            $table->boolean('negotiable')->default(false);
            $table->string('condition', 16)->nullable()->index();
            $table->jsonb('attribute_values')->default('{}');
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 16)->nullable();
        });

        ListingSchema::indexes('classifieds', promotable: true);
        DB::statement('CREATE INDEX classifieds_attribute_values_index ON classifieds USING gin (attribute_values jsonb_path_ops)');
    }
};
