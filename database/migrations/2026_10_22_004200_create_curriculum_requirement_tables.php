<?php

use App\Support\Database\Constraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Graduation requirements per program, program role (primary, second major, minor) and
 * range of catalog years (docs/porting-plan.md section 10.4). Phase 8 evaluates them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_requirement_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->string('program_role', 16);
            $table->smallInteger('catalog_year_from');
            // Null: applies to all later catalog years until replaced.
            $table->smallInteger('catalog_year_to')->nullable();
            $table->jsonb('name');
            $table->decimal('total_credits', 5, 1)->nullable();
            $table->jsonb('note')->nullable();
            $table->timestampsTz();

            $table->index(['program_id', 'program_role']);
        });

        Schema::create('curriculum_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_requirement_set_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 32);
            $table->jsonb('name')->nullable();
            $table->foreignId('course_category_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('min_credits', 5, 1)->nullable();
            $table->smallInteger('min_courses')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->index('curriculum_requirement_set_id');
        });

        Schema::create('curriculum_requirement_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_requirement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['curriculum_requirement_id', 'course_id']);
        });

        Constraint::translatable('curriculum_requirement_sets', 'name');
        Constraint::translatable('curriculum_requirement_sets', 'note', nullable: true);
        Constraint::translatable('curriculum_requirements', 'name', nullable: true);

        DB::statement(<<<'SQL'
            ALTER TABLE curriculum_requirement_sets
                ADD COLUMN catalog_years int4range GENERATED ALWAYS AS (int4range(catalog_year_from, catalog_year_to, '[]')) STORED,
                ADD CONSTRAINT curriculum_requirement_sets_role_check CHECK (program_role IN ('primary', 'second_major', 'minor')),
                ADD CONSTRAINT curriculum_requirement_sets_years_check CHECK (catalog_year_to IS NULL OR catalog_year_to >= catalog_year_from),
                ADD CONSTRAINT curriculum_requirement_sets_no_overlap_excl EXCLUDE USING gist (program_id WITH =, program_role WITH =, catalog_years WITH &&)
            SQL);
        // category_credits: credits in a category; required_courses: every listed course;
        // course_group: a number of credits or courses from the listed courses.
        DB::statement(<<<'SQL'
            ALTER TABLE curriculum_requirements ADD CONSTRAINT curriculum_requirements_kind_check CHECK (
                (kind = 'category_credits' AND course_category_id IS NOT NULL AND min_credits IS NOT NULL)
                OR (kind = 'required_courses' AND course_category_id IS NULL)
                OR (kind = 'course_group' AND course_category_id IS NULL AND (min_credits IS NOT NULL OR min_courses IS NOT NULL))
            )
            SQL);

        Constraint::audit(['curriculum_requirement_sets', 'curriculum_requirements', 'curriculum_requirement_courses']);
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_requirement_courses');
        Schema::dropIfExists('curriculum_requirements');
        Schema::dropIfExists('curriculum_requirement_sets');
    }
};
