<?php

use App\Support\Database\Constraint;
use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Degree audits (legacy report_chkgrad printed credits without judging them), requirement
 * waivers, and degree number sequences (porting/decisions/0016-graduation-and-reporting.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('degree_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_record_id')->constrained()->restrictOnDelete();
            // Null when no requirement set covers the student's catalog year (reported as not satisfied).
            $table->foreignId('curriculum_requirement_set_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('program_role', 16);
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('is_satisfied');
            $table->decimal('earned_credits', 5, 1);
            $table->decimal('required_credits', 5, 1)->nullable();
            $table->decimal('cumulative_gpa', 4, 2)->nullable();
            // The full result: requirements with achieved/required/satisfied and the courses counted.
            $table->jsonb('result');
            $table->timestampTz('audited_at');
            $table->foreignId('audited_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['student_record_id', 'program_role', 'audited_at']);
        });

        Schema::create('degree_requirement_waivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curriculum_requirement_id')->constrained()->cascadeOnDelete();
            $table->text('reason');
            // pending -> approved | rejected; approved -> lifted. Only approved waivers count.
            $table->string('status', 16)->default('pending');
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('requested_at');
            $table->foreignId('decided_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->foreignId('lifted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('lifted_at')->nullable();
            $table->text('lift_reason')->nullable();
            $table->timestampsTz();

            $table->index(['student_record_id', 'status']);
        });

        Schema::create('degree_number_sequences', function (Blueprint $table) {
            $table->string('prefix', 64)->primary();
            $table->bigInteger('last_value')->default(0);
            $table->timestampsTz();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE degree_audits
                ADD CONSTRAINT degree_audits_role_check CHECK (program_role IN ('primary', 'second_major', 'minor'))
            SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE degree_requirement_waivers
                ADD CONSTRAINT degree_requirement_waivers_status_check CHECK (status IN ('pending', 'approved', 'rejected', 'lifted')),
                ADD CONSTRAINT degree_requirement_waivers_decision_check CHECK ((status = 'pending') = (decided_at IS NULL)),
                ADD CONSTRAINT degree_requirement_waivers_lift_check CHECK ((status = 'lifted') = (lifted_at IS NOT NULL))
            SQL);
        DB::statement("CREATE UNIQUE INDEX degree_requirement_waivers_open_unique ON degree_requirement_waivers (student_record_id, curriculum_requirement_id) WHERE status IN ('pending', 'approved')");

        Constraint::audit(['degree_requirement_waivers']);
        SqlFile::apply('policies/graduation.sql');
    }

    public function down(): void
    {
        Schema::dropIfExists('degree_number_sequences');
        Schema::dropIfExists('degree_requirement_waivers');
        Schema::dropIfExists('degree_audits');
    }
};
