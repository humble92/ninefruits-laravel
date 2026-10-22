<?php

use App\Support\Database\Constraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reference data: ISO countries and languages, institution specific code data
 * (legacy FCOUNTRY, FLANGUAGE, FNATION, FADMDIST).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->char('code', 2)->unique(); // ISO 3166-1 alpha-2
            $table->char('alpha3_code', 3)->unique();
            $table->char('numeric_code', 3)->nullable();
            $table->jsonb('name');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // ISO 639-1
            $table->char('alpha3_code', 3)->nullable();
            $table->jsonb('name');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('lookup_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->jsonb('name');
            $table->jsonb('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestampsTz();
        });

        Schema::create('lookup_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lookup_type_id')->constrained()->cascadeOnDelete();
            $table->string('code', 64);
            $table->jsonb('name');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['lookup_type_id', 'code']);
        });

        foreach (['countries', 'languages', 'lookup_types', 'lookup_values'] as $table) {
            Constraint::translatable($table, 'name');
        }
        Constraint::translatable('lookup_types', 'description', nullable: true);

        Constraint::audit(['countries', 'languages', 'lookup_types', 'lookup_values']);
    }

    public function down(): void
    {
        Schema::dropIfExists('lookup_values');
        Schema::dropIfExists('lookup_types');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('countries');
    }
};
