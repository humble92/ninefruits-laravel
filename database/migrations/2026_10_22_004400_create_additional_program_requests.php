<?php

use App\Support\Database\Constraint;
use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Students apply for a second major or minor in the portal; staff of the target program
 * approve, which adds a student_record_programs row. Programs say which they accept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->boolean('allows_second_major')->default(false);
            $table->boolean('allows_minor')->default(false);
        });

        Schema::create('additional_program_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            // The target program's unit, for scoped approval (as on student records).
            $table->foreignId('org_unit_id')->constrained()->restrictOnDelete();
            $table->string('role', 16);
            // Term of the application window; the program starts with this term.
            $table->foreignId('term_id')->constrained()->restrictOnDelete();
            $table->string('status', 16)->default('requested');
            $table->text('statement')->nullable();
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('requested_at');
            $table->foreignId('decided_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->foreignId('student_record_program_id')->nullable()->constrained()->nullOnDelete();
            $table->timestampsTz();

            $table->index(['student_record_id', 'status']);
            $table->index(['org_unit_id', 'status']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE additional_program_requests
                ADD CONSTRAINT additional_program_requests_role_check CHECK (role IN ('second_major', 'minor')),
                ADD CONSTRAINT additional_program_requests_status_check CHECK (status IN ('requested', 'approved', 'rejected', 'cancelled'))
            SQL);
        DB::statement("CREATE UNIQUE INDEX additional_program_requests_single_pending_unique ON additional_program_requests (student_record_id, program_id) WHERE status = 'requested'");
        DB::statement("ALTER TABLE student_record_programs ADD CONSTRAINT student_record_programs_role_check CHECK (role IN ('second_major', 'minor'))");
        // A program ended earlier (e.g. a minor given up) may be taken again: unique among current rows only.
        Schema::table('student_record_programs', fn (Blueprint $table) => $table->dropUnique(['student_record_id', 'program_id']));
        DB::statement('CREATE UNIQUE INDEX student_record_programs_current_unique ON student_record_programs (student_record_id, program_id) WHERE end_date IS NULL');

        SqlFile::apply('policies/additional_programs.sql');

        Constraint::audit(['additional_program_requests']);
    }

    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS student_records_requested_programs_select ON student_records');
        DB::statement('DROP POLICY IF EXISTS student_records_additional_programs_select ON student_records');
        Schema::dropIfExists('additional_program_requests');
        DB::statement('DROP FUNCTION IF EXISTS util.additional_program_request_record_ids()');
        DB::statement('ALTER TABLE student_record_programs DROP CONSTRAINT IF EXISTS student_record_programs_role_check');
        DB::statement('DROP INDEX IF EXISTS student_record_programs_current_unique');
        Schema::table('student_record_programs', fn (Blueprint $table) => $table->unique(['student_record_id', 'program_id']));
        Schema::table('programs', fn (Blueprint $table) => $table->dropColumn(['allows_second_major', 'allows_minor']));
    }
};
