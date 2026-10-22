<?php

use App\Support\Database\Constraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Courses (legacy SFOBJECT): an unchanging identity with dated versions (title, credits,
 * hours, category), so records keep pointing at the version that was taught. Requisites
 * belong to a version; equivalencies (substitutions) have their own periods.
 * docs/porting-plan.md section 10.4.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            // Managing unit: who maintains the course and offers it by default.
            $table->foreignId('org_unit_id')->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('course_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->date('effective_start_date');
            $table->date('effective_end_date')->nullable();
            $table->jsonb('title');
            $table->jsonb('description')->nullable();
            $table->decimal('credits', 4, 1);
            $table->decimal('lecture_hours', 4, 1)->default(0);
            $table->decimal('lab_hours', 4, 1)->default(0);
            $table->foreignId('course_category_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('grading_basis', 16)->default('graded');
            // Legacy credit bank courses (codes 009*, 900*) did not count toward the limit.
            $table->boolean('counts_toward_credit_limit')->default(true);
            $table->boolean('is_repeatable')->default(false);
            $table->timestampsTz();

            $table->index('course_id');
            // Target of the composite foreign key from sections.
            $table->unique(['id', 'course_id']);
        });

        Schema::create('course_requisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_version_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 16);
            // Courses in the same group are alternatives (OR); groups must all be met (AND).
            $table->smallInteger('group_number')->default(1);
            $table->foreignId('required_course_id')->constrained('courses')->restrictOnDelete();
            // Grade code on the default scale, e.g. C0. Null: passing.
            $table->string('minimum_grade', 8)->nullable();
            // Prerequisite that may also be taken in the same term.
            $table->boolean('allows_concurrent')->default(false);
            $table->timestampsTz();

            $table->unique(['course_version_id', 'kind', 'group_number', 'required_course_id']);
        });

        Schema::create('course_equivalencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equivalent_course_id')->constrained('courses')->restrictOnDelete();
            // Mutual: each counts for the other. Otherwise only equivalent_course counts for course.
            $table->boolean('is_mutual')->default(true);
            $table->date('effective_start_date');
            $table->date('effective_end_date')->nullable();
            $table->jsonb('note')->nullable();
            $table->timestampsTz();
        });

        Constraint::translatable('course_versions', 'title');
        Constraint::translatable('course_versions', 'description', nullable: true);
        Constraint::translatable('course_equivalencies', 'note', nullable: true);
        Constraint::translationSearchIndex('course_versions', 'title');
        Constraint::datePeriod('course_versions', 'effective_period', 'effective_start_date', 'effective_end_date');
        Constraint::noOverlap('course_versions', 'effective_period', ['course_id']);
        Constraint::datePeriod('course_equivalencies', 'effective_period', 'effective_start_date', 'effective_end_date');
        Constraint::noOverlap('course_equivalencies', 'effective_period', ['course_id', 'equivalent_course_id']);

        DB::statement(<<<'SQL'
            ALTER TABLE course_versions
                ADD CONSTRAINT course_versions_credits_check CHECK (credits >= 0 AND lecture_hours >= 0 AND lab_hours >= 0),
                ADD CONSTRAINT course_versions_grading_basis_check CHECK (grading_basis IN ('graded', 'pass_fail'))
            SQL);
        DB::statement("ALTER TABLE course_requisites ADD CONSTRAINT course_requisites_kind_check CHECK (kind IN ('prerequisite', 'corequisite'))");
        DB::statement('ALTER TABLE course_requisites ADD CONSTRAINT course_requisites_group_check CHECK (group_number >= 1)');
        DB::statement('ALTER TABLE course_equivalencies ADD CONSTRAINT course_equivalencies_distinct_check CHECK (course_id <> equivalent_course_id)');

        Constraint::audit(['courses', 'course_versions', 'course_requisites', 'course_equivalencies']);
    }

    public function down(): void
    {
        Schema::dropIfExists('course_equivalencies');
        Schema::dropIfExists('course_requisites');
        Schema::dropIfExists('course_versions');
        Schema::dropIfExists('courses');
    }
};
