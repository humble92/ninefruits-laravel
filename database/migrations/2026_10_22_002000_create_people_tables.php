<?php

use App\Support\Database\Constraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * People (one row per person for life), their guardians and instructor records
 * (legacy SRREGISTRY/SGREGISTRY personal columns, SRFAMILY, FACULTY).
 * porting/porting-plan.md section 10.1. Row level security: 2026_10_22_002300.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            // Romanized legal name; variants per language or script, e.g. {"ko": "김민수"}.
            $table->string('family_name');
            $table->string('given_name')->nullable();
            $table->jsonb('name_variants')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 16)->nullable();
            $table->foreignId('nationality_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('ethnicity_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code', 16)->nullable();
            $table->text('national_id_encrypted')->nullable();
            $table->char('national_id_hash', 64)->nullable()->index();
            $table->text('passport_number_encrypted')->nullable();
            $table->char('passport_number_hash', 64)->nullable()->index();
            $table->foreignId('merged_into_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['family_name', 'given_name']);
            $table->index('birth_date');
        });

        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('relationship', 32);
            $table->string('full_name');
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_primary_contact')->default(false);
            $table->timestampsTz();

            $table->index('person_id');
        });

        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->unique()->constrained('people')->restrictOnDelete();
            $table->string('employee_number', 32)->unique();
            $table->foreignId('org_unit_id')->constrained()->restrictOnDelete();
            $table->string('employment_type', 32);
            $table->jsonb('academic_title')->nullable();
            $table->date('hire_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestampsTz();

            $table->index('org_unit_id');
        });

        Constraint::translatable('people', 'name_variants', nullable: true);
        Constraint::translatable('instructors', 'academic_title', nullable: true);
        DB::statement('ALTER TABLE people ADD CONSTRAINT people_not_merged_into_self_check CHECK (merged_into_person_id IS NULL OR merged_into_person_id <> id)');
        DB::statement('ALTER TABLE instructors ADD CONSTRAINT instructors_period_check CHECK (end_date IS NULL OR hire_date IS NULL OR end_date >= hire_date)');
        DB::statement("CREATE INDEX people_name_search_index ON people USING gin ((family_name || ' ' || coalesce(given_name, '') || ' ' || util.i18n_search_text(name_variants)) gin_trgm_ops)");

        DB::select("SELECT util.enable_audit('people', ARRAY['national_id_encrypted', 'passport_number_encrypted'])");
        Constraint::audit(['guardians', 'instructors']);
    }

    public function down(): void
    {
        Schema::dropIfExists('instructors');
        Schema::dropIfExists('guardians');
        Schema::dropIfExists('people');
    }
};
