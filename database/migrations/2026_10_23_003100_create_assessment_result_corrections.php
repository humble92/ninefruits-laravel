<?php

use App\Support\Database\Constraint;
use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Once a session's results are published they are locked (decided 2026-10-23): a change
 * goes through a correction request with a reason, approved by someone else with
 * assessments.manage, like grade changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_result_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_result_id')->constrained()->cascadeOnDelete();
            // The proposed values: scores (component code => score), total, passed, note.
            $table->jsonb('proposed');
            $table->jsonb('previous');
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
            ALTER TABLE assessment_result_corrections
                ADD CONSTRAINT assessment_result_corrections_status_check CHECK (status IN ('pending', 'approved', 'rejected', 'cancelled')),
                ADD CONSTRAINT assessment_result_corrections_decision_check CHECK ((status = 'pending') = (decided_at IS NULL))
            SQL);
        DB::statement("CREATE UNIQUE INDEX assessment_result_corrections_single_pending_unique ON assessment_result_corrections (assessment_result_id) WHERE status = 'pending'");

        Constraint::audit(['assessment_result_corrections']);
        SqlFile::apply('policies/assessment_corrections.sql');
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_result_corrections');
    }
};
