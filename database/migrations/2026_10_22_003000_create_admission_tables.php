<?php

use App\Support\Database\Constraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admissions (new, no legacy equivalent): cycles that may stay open without a deadline,
 * offered programs, required documents and applications. porting/porting-plan.md sections
 * 10.1 and 10.2. The admission term is chosen at matriculation, not on the cycle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->jsonb('name');
            $table->jsonb('description')->nullable();
            $table->timestampTz('opens_at');
            // Null: rolling admission without a deadline.
            $table->timestampTz('closes_at')->nullable();
            $table->boolean('is_published')->default(false);
            // Rolling admission usually tells each applicant as soon as they are decided.
            $table->boolean('should_release_decisions_immediately')->default(false);
            // Days an admitted applicant has to accept after the decision is released.
            $table->smallInteger('offer_response_days')->nullable();
            $table->timestampsTz();
        });

        // Application numbers per cycle. Not audited (a counter, not business data).
        Schema::create('application_number_sequences', function (Blueprint $table) {
            $table->foreignId('admission_cycle_id')->primary()->constrained()->cascadeOnDelete();
            $table->integer('last_value')->default(0);
        });

        Schema::create('admission_cycle_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->integer('capacity')->nullable();
            $table->timestampsTz();

            $table->unique(['admission_cycle_id', 'program_id']);
        });

        Schema::create('admission_cycle_document_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('lookup_values')->restrictOnDelete();
            $table->boolean('is_required')->default(true);
            $table->timestampsTz();

            $table->unique(['admission_cycle_id', 'document_type_id']);
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            // Assigned at submission, per cycle: {cycle code}-{00001}.
            $table->string('application_number', 80)->nullable()->unique();
            $table->foreignId('admission_cycle_id')->constrained()->restrictOnDelete();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            // The program's org unit, for scoped staff access (as on student_records).
            $table->foreignId('org_unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('admission_type_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->string('previous_school_name')->nullable();
            $table->foreignId('previous_school_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->date('previous_graduation_date')->nullable();
            $table->text('personal_statement')->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('review_started_at')->nullable();
            $table->foreignId('decided_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestampTz('decision_released_at')->nullable();
            $table->timestampTz('offer_expires_at')->nullable();
            $table->timestampTz('responded_at')->nullable();
            $table->timestampTz('withdrawn_at')->nullable();
            $table->text('withdrawal_reason')->nullable();
            $table->foreignId('student_record_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->timestampTz('matriculated_at')->nullable();
            $table->timestampsTz();

            $table->index('person_id');
            $table->index(['admission_cycle_id', 'status']);
            $table->index(['org_unit_id', 'status']);
            $table->index(['program_id', 'status']);
        });

        Constraint::translatable('admission_cycles', 'name');
        Constraint::translatable('admission_cycles', 'description', nullable: true);
        DB::statement('ALTER TABLE admission_cycles ADD CONSTRAINT admission_cycles_period_check CHECK (closes_at IS NULL OR closes_at > opens_at)');
        DB::statement('ALTER TABLE admission_cycle_programs ADD CONSTRAINT admission_cycle_programs_capacity_check CHECK (capacity IS NULL OR capacity >= 0)');
        DB::statement(<<<'SQL'
            ALTER TABLE applications
                ADD CONSTRAINT applications_status_check CHECK (status IN ('draft', 'submitted', 'under_review', 'admitted', 'rejected', 'confirmed', 'withdrawn', 'expired')),
                -- A number exactly when submitted; every state after a draft needs one,
                -- except withdrawing or expiring a draft.
                ADD CONSTRAINT applications_number_check CHECK ((submitted_at IS NULL) = (application_number IS NULL)),
                ADD CONSTRAINT applications_submitted_check CHECK (status IN ('draft', 'expired', 'withdrawn') OR submitted_at IS NOT NULL),
                ADD CONSTRAINT applications_matriculated_check CHECK (student_record_id IS NULL OR status = 'confirmed')
            SQL);
        // One open application per person and cycle; a closed one (withdrawn, rejected,
        // expired) does not block applying again.
        DB::statement("CREATE UNIQUE INDEX applications_single_open_unique ON applications (person_id, admission_cycle_id) WHERE status NOT IN ('withdrawn', 'rejected', 'expired')");

        Constraint::audit(['admission_cycles', 'admission_cycle_programs', 'admission_cycle_document_types', 'applications']);
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
        Schema::dropIfExists('admission_cycle_document_types');
        Schema::dropIfExists('admission_cycle_programs');
        Schema::dropIfExists('application_number_sequences');
        Schema::dropIfExists('admission_cycles');
    }
};
