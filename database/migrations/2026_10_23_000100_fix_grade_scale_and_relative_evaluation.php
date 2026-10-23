<?php

use App\Support\Database\Constraint;
use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Grading corrections decided 2026-10-23 (porting/decisions/0011-grades-and-term-records.md):
 * the grade scale is fixed at installation, so the comparison grade goes; sections are
 * evaluated absolutely unless the instructor asks for relative evaluation (requests with
 * approval); relative grades must follow the institution's grade caps, and staff who
 * submit outside them do so as a recorded exception.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE enrollments DROP CONSTRAINT IF EXISTS enrollments_comparison_grade_check');
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('comparison_grade_scale_id');
            $table->dropColumn(['comparison_grade_code', 'comparison_grade_points']);
        });
        SqlFile::apply('triggers/grades.sql');

        DB::statement("ALTER TABLE sections ALTER COLUMN evaluation_method SET DEFAULT 'absolute'");
        Schema::table('sections', function (Blueprint $table) {
            // Relative grades submitted by staff outside the grade caps: shown as an exception.
            $table->text('grade_exception_reason')->nullable();
            $table->foreignId('grade_exception_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('grade_exception_at')->nullable();
        });
        DB::statement('ALTER TABLE sections ADD CONSTRAINT sections_grade_exception_check CHECK ((grade_exception_reason IS NULL) = (grade_exception_at IS NULL))');

        Schema::create('evaluation_method_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->restrictOnDelete();
            $table->string('requested_method', 16);
            $table->text('reason');
            $table->string('status', 16)->default('pending');
            $table->foreignId('requested_by_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('requested_at');
            $table->foreignId('decided_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestampsTz();

            $table->index(['status', 'requested_at']);
        });
        DB::statement(<<<'SQL'
            ALTER TABLE evaluation_method_requests
                ADD CONSTRAINT evaluation_method_requests_method_check CHECK (requested_method IN ('absolute', 'relative')),
                ADD CONSTRAINT evaluation_method_requests_status_check CHECK (status IN ('pending', 'approved', 'rejected', 'cancelled')),
                ADD CONSTRAINT evaluation_method_requests_decision_check CHECK ((status = 'pending') = (decided_at IS NULL))
            SQL);
        DB::statement("CREATE UNIQUE INDEX evaluation_method_requests_single_pending_unique ON evaluation_method_requests (section_id) WHERE status = 'pending'");

        Constraint::audit(['evaluation_method_requests']);
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_method_requests');
        DB::statement('ALTER TABLE sections DROP CONSTRAINT IF EXISTS sections_grade_exception_check');
        Schema::table('sections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grade_exception_by_id');
            $table->dropColumn(['grade_exception_reason', 'grade_exception_at']);
        });
        DB::statement("ALTER TABLE sections ALTER COLUMN evaluation_method SET DEFAULT 'relative'");
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('comparison_grade_scale_id')->nullable()->constrained('grade_scales')->restrictOnDelete();
            $table->string('comparison_grade_code', 8)->nullable();
            $table->decimal('comparison_grade_points', 4, 2)->nullable();
        });
    }
};
