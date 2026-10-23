<?php

use App\Support\Database\Constraint;
use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Grades (legacy SSCORE grade columns, SSCORE_EDIT_BACKUP): grade entry and finalization on
 * enrollments, grade change requests with a second approver, and term records (GPA and
 * academic standing per student and term; the legacy computed them live in views).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Which courses a grade is for: letter grades for graded courses, P/NP for pass/fail
        // courses, both for e.g. I (incomplete).
        Schema::table('grade_scale_items', function (Blueprint $table) {
            $table->string('applies_to', 16)->default('graded');
        });
        DB::statement("UPDATE grade_scale_items SET applies_to = 'pass_fail' WHERE NOT counts_in_gpa");
        DB::statement("ALTER TABLE grade_scale_items ADD CONSTRAINT grade_scale_items_applies_to_check CHECK (applies_to IN ('graded', 'pass_fail', 'both'))");

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('grade_scale_id')->nullable()->constrained()->restrictOnDelete();
            // Optional percentage; the grade code is what counts.
            $table->decimal('score', 5, 2)->nullable();
            // Snapshot of the scale item when the grade was given.
            $table->decimal('grade_points', 4, 2)->nullable();
            $table->boolean('counts_in_gpa')->nullable();
            $table->timestampTz('grade_entered_at')->nullable();
            $table->foreignId('grade_entered_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('grade_submitted_at')->nullable();
            $table->foreignId('grade_submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('grade_finalized_at')->nullable();
            $table->foreignId('grade_finalized_by_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('grade_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->string('from_grade_code', 8)->nullable();
            $table->string('to_grade_code', 8);
            $table->decimal('to_score', 5, 2)->nullable();
            $table->text('reason');
            $table->string('status', 16)->default('pending');
            $table->foreignId('requested_by_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('requested_at');
            $table->foreignId('decided_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestampTz('applied_at')->nullable();
            $table->timestampsTz();

            $table->index(['status', 'requested_at']);
        });

        Schema::create('term_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_record_id')->constrained()->restrictOnDelete();
            $table->foreignId('term_id')->constrained()->restrictOnDelete();
            // This term: completed courses (attempted), passed (earned), graded for GPA.
            $table->decimal('attempted_credits', 5, 1)->default(0);
            $table->decimal('earned_credits', 5, 1)->default(0);
            $table->decimal('gpa_credits', 5, 1)->default(0);
            $table->decimal('grade_points_total', 8, 2)->default(0);
            $table->decimal('term_gpa', 5, 3)->nullable();
            // Up to and including this term, retakes counted by the retake policy.
            $table->decimal('cumulative_attempted_credits', 6, 1)->default(0);
            $table->decimal('cumulative_earned_credits', 6, 1)->default(0);
            $table->decimal('cumulative_gpa_credits', 6, 1)->default(0);
            $table->decimal('cumulative_grade_points_total', 9, 2)->default(0);
            $table->decimal('cumulative_gpa', 5, 3)->nullable();
            $table->string('academic_standing', 32)->nullable();
            $table->integer('warning_count')->default(0);
            $table->boolean('is_honors')->default(false);
            $table->integer('honors_rank')->nullable();
            $table->timestampTz('calculated_at');
            // Set by term close: the record is the term's official snapshot from then on.
            $table->timestampTz('closed_at')->nullable();
            $table->timestampsTz();

            $table->unique(['student_record_id', 'term_id']);
            $table->index(['term_id', 'academic_standing']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE enrollments
                ADD CONSTRAINT enrollments_score_check CHECK (score IS NULL OR score BETWEEN 0 AND 100),
                ADD CONSTRAINT enrollments_grade_check CHECK (
                    (grade_status = 'none' AND grade_code IS NULL)
                    OR (grade_status = 'draft')
                    OR (grade_status IN ('submitted', 'finalized') AND grade_code IS NOT NULL AND grade_scale_id IS NOT NULL)
                ),
                ADD CONSTRAINT enrollments_finalized_check CHECK ((grade_status = 'finalized') = (status = 'completed'))
            SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE grade_change_requests
                ADD CONSTRAINT grade_change_requests_status_check CHECK (status IN ('pending', 'approved', 'rejected', 'cancelled')),
                ADD CONSTRAINT grade_change_requests_score_check CHECK (to_score IS NULL OR to_score BETWEEN 0 AND 100),
                -- Separation of duties: nobody approves their own request.
                ADD CONSTRAINT grade_change_requests_second_person_check CHECK (decided_by_id IS NULL OR decided_by_id <> requested_by_id),
                ADD CONSTRAINT grade_change_requests_decision_check CHECK ((status = 'pending') = (decided_at IS NULL)),
                ADD CONSTRAINT grade_change_requests_applied_check CHECK (applied_at IS NULL OR status = 'approved')
            SQL);
        // One open request per enrollment.
        DB::statement("CREATE UNIQUE INDEX grade_change_requests_single_pending_unique ON grade_change_requests (enrollment_id) WHERE status = 'pending'");
        // GPA columns keep the decimals they were calculated with (grading.gpa_decimals).
        DB::statement('ALTER TABLE term_records ALTER COLUMN term_gpa TYPE numeric, ALTER COLUMN cumulative_gpa TYPE numeric');
        DB::statement(<<<'SQL'
            ALTER TABLE term_records
                ADD CONSTRAINT term_records_gpa_check CHECK ((term_gpa IS NULL OR term_gpa BETWEEN 0 AND 10) AND (cumulative_gpa IS NULL OR cumulative_gpa BETWEEN 0 AND 10)),
                ADD CONSTRAINT term_records_standing_check CHECK (academic_standing IS NULL OR academic_standing IN ('good', 'warning', 'dismissal_review')),
                ADD CONSTRAINT term_records_credits_check CHECK (earned_credits <= attempted_credits AND gpa_credits <= attempted_credits)
            SQL);

        SqlFile::apply('triggers/grades.sql');
        Constraint::audit(['grade_change_requests', 'term_records']);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS enrollments_guard_finalized_grade ON enrollments');
        DB::statement('DROP FUNCTION IF EXISTS util.guard_finalized_grade()');
        Schema::dropIfExists('term_records');
        Schema::dropIfExists('grade_change_requests');
        DB::statement('ALTER TABLE enrollments DROP CONSTRAINT IF EXISTS enrollments_score_check, DROP CONSTRAINT IF EXISTS enrollments_grade_check, DROP CONSTRAINT IF EXISTS enrollments_finalized_check');
        DB::statement('ALTER TABLE grade_scale_items DROP CONSTRAINT IF EXISTS grade_scale_items_applies_to_check');
        Schema::table('grade_scale_items', function (Blueprint $table) {
            $table->dropColumn('applies_to');
        });
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grade_scale_id');
            $table->dropConstrainedForeignId('grade_entered_by_id');
            $table->dropConstrainedForeignId('grade_submitted_by_id');
            $table->dropConstrainedForeignId('grade_finalized_by_id');
            $table->dropColumn(['score', 'grade_points', 'counts_in_gpa', 'grade_entered_at', 'grade_submitted_at', 'grade_finalized_at']);
        });
    }
};
