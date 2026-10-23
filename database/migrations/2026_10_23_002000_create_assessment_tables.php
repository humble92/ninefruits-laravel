<?php

use App\Support\Database\Constraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Language and comprehensive examinations, generalized (legacy CET with fixed score columns
 * per area, SSCORE_ADD with one P/N per exam, COMEXAM for computer certificates): a kind
 * with its own scoring areas and pass rule, sessions with registration, results with scores
 * per area, external results with evidence, and a curriculum requirement "passed kind X".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->jsonb('name');
            $table->string('category', 16)->default('comprehensive');
            // score: total of the areas against pass_score (and area minimums); pass_fail: decided by hand.
            $table->string('scoring', 16)->default('score');
            $table->decimal('max_score', 6, 1)->nullable();
            $table->decimal('pass_score', 6, 1)->nullable();
            // Results from an outside body (TOPIK, HSK, ...) recorded with evidence, no session.
            $table->boolean('allows_external')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();
        });

        Schema::create('assessment_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_type_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32);
            $table->jsonb('name');
            $table->decimal('max_score', 6, 1);
            $table->decimal('min_score', 6, 1)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->unique(['assessment_type_id', 'code']);
        });

        Schema::create('assessment_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_type_id')->constrained()->restrictOnDelete();
            $table->date('held_date');
            $table->string('venue', 120)->nullable();
            $table->timestampTz('registration_opens_at')->nullable();
            $table->timestampTz('registration_closes_at')->nullable();
            $table->integer('capacity')->nullable();
            // Overrides the kind's pass score for this session (legacy CET_CUTLINE).
            $table->decimal('pass_score', 6, 1)->nullable();
            $table->timestampTz('results_published_at')->nullable();
            $table->timestampsTz();

            $table->unique(['assessment_type_id', 'held_date']);
        });

        Schema::create('assessment_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_record_id')->constrained()->restrictOnDelete();
            $table->string('status', 16)->default('registered');
            $table->timestampTz('registered_at');
            // Null when the student registered themselves.
            $table->foreignId('registered_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestampsTz();

            $table->unique(['assessment_session_id', 'student_record_id']);
            $table->index('student_record_id');
        });

        Schema::create('assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_record_id')->constrained()->restrictOnDelete();
            $table->foreignId('assessment_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('assessment_session_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('assessment_registration_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('source', 16);
            $table->date('taken_date');
            $table->decimal('total_score', 6, 1)->nullable();
            // Level or grade of an external test (TOPIK 5, HSK 6).
            $table->string('level', 32)->nullable();
            $table->boolean('is_passed');
            $table->string('certificate_number', 64)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('entered_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('entered_at');
            $table->timestampsTz();

            $table->unique(['assessment_session_id', 'student_record_id']);
            $table->index(['student_record_id', 'assessment_type_id']);
        });

        Schema::create('assessment_component_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_component_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 6, 1);
            $table->timestampsTz();

            $table->unique(['assessment_result_id', 'assessment_component_id']);
        });

        Schema::table('curriculum_requirements', function (Blueprint $table) {
            $table->foreignId('assessment_type_id')->nullable()->after('course_category_id')->constrained()->restrictOnDelete();
        });

        Constraint::translatable('assessment_types', 'name');
        Constraint::translatable('assessment_components', 'name');

        DB::statement(<<<'SQL'
            ALTER TABLE assessment_types
                ADD CONSTRAINT assessment_types_category_check CHECK (category IN ('language', 'comprehensive', 'thesis', 'external')),
                ADD CONSTRAINT assessment_types_scoring_check CHECK (scoring IN ('score', 'pass_fail'))
            SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE assessment_sessions
                ADD CONSTRAINT assessment_sessions_registration_check
                    CHECK (registration_opens_at IS NULL OR registration_closes_at IS NULL OR registration_opens_at < registration_closes_at),
                ADD CONSTRAINT assessment_sessions_capacity_check CHECK (capacity IS NULL OR capacity > 0)
            SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE assessment_registrations
                ADD CONSTRAINT assessment_registrations_status_check CHECK (status IN ('registered', 'cancelled', 'absent')),
                ADD CONSTRAINT assessment_registrations_cancelled_check CHECK ((status = 'cancelled') = (cancelled_at IS NOT NULL))
            SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE assessment_results
                ADD CONSTRAINT assessment_results_source_check CHECK (source IN ('session', 'external')),
                ADD CONSTRAINT assessment_results_session_check CHECK ((source = 'session') = (assessment_session_id IS NOT NULL) AND (source = 'session') = (assessment_registration_id IS NOT NULL))
            SQL);
        DB::statement('ALTER TABLE curriculum_requirements DROP CONSTRAINT curriculum_requirements_kind_check');
        DB::statement(<<<'SQL'
            ALTER TABLE curriculum_requirements ADD CONSTRAINT curriculum_requirements_kind_check CHECK (
                (kind = 'category_credits' AND course_category_id IS NOT NULL AND min_credits IS NOT NULL AND assessment_type_id IS NULL)
                OR (kind = 'required_courses' AND course_category_id IS NULL AND assessment_type_id IS NULL)
                OR (kind = 'course_group' AND course_category_id IS NULL AND assessment_type_id IS NULL AND (min_credits IS NOT NULL OR min_courses IS NOT NULL))
                OR (kind = 'assessment_pass' AND assessment_type_id IS NOT NULL AND course_category_id IS NULL AND min_credits IS NULL AND min_courses IS NULL)
            )
            SQL);

        Constraint::audit(['assessment_types', 'assessment_components', 'assessment_sessions', 'assessment_registrations', 'assessment_results', 'assessment_component_scores']);
    }

    public function down(): void
    {
        DB::statement("DELETE FROM curriculum_requirements WHERE kind = 'assessment_pass'");
        DB::statement('ALTER TABLE curriculum_requirements DROP CONSTRAINT curriculum_requirements_kind_check');
        Schema::table('curriculum_requirements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assessment_type_id');
        });
        DB::statement(<<<'SQL'
            ALTER TABLE curriculum_requirements ADD CONSTRAINT curriculum_requirements_kind_check CHECK (
                (kind = 'category_credits' AND course_category_id IS NOT NULL AND min_credits IS NOT NULL)
                OR (kind = 'required_courses' AND course_category_id IS NULL)
                OR (kind = 'course_group' AND course_category_id IS NULL AND (min_credits IS NOT NULL OR min_courses IS NOT NULL))
            )
            SQL);
        Schema::dropIfExists('assessment_component_scores');
        Schema::dropIfExists('assessment_results');
        Schema::dropIfExists('assessment_registrations');
        Schema::dropIfExists('assessment_sessions');
        Schema::dropIfExists('assessment_components');
        Schema::dropIfExists('assessment_types');
    }
};
