<?php

use App\Support\Database\Constraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Student records (one per admission, new student number each time), additional programs,
 * status changes and advisors (legacy SRREGISTRY/SGREGISTRY, SRALTER, SRALTERDEF,
 * SR_ADVISER). docs/porting-plan.md section 10.1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_status_change_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->jsonb('name');
            $table->string('to_status', 32);
            $table->jsonb('from_statuses');
            $table->boolean('can_student_request')->default(false);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();
        });

        Schema::create('student_number_sequences', function (Blueprint $table) {
            $table->string('prefix', 64)->primary();
            $table->bigInteger('last_value')->default(0);
            $table->timestampsTz();
        });

        Schema::create('student_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->string('student_number', 32)->unique();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            // Managing org unit, normally the primary program's unit. Used for scoped access.
            $table->foreignId('org_unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('admission_term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->foreignId('admission_type_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->date('admission_date');
            $table->smallInteger('catalog_year');
            $table->smallInteger('year_level')->default(1);
            $table->string('status', 32)->default('enrolled');
            $table->timestampTz('status_changed_at')->nullable();
            $table->string('academic_standing', 32)->nullable();
            $table->date('graduation_date')->nullable();
            $table->string('degree_number', 64)->nullable()->unique();
            $table->string('institutional_email')->nullable();
            $table->jsonb('tags')->nullable();
            $table->timestampsTz();

            $table->index('person_id');
            $table->index(['org_unit_id', 'status']);
            $table->index(['program_id', 'status']);
            $table->index('institutional_email');
        });

        Schema::create('student_record_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->string('role', 32);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestampsTz();

            $table->unique(['student_record_id', 'program_id']);
        });

        Schema::create('student_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('status_change_type_id')->constrained('student_status_change_types')->restrictOnDelete();
            $table->string('from_status', 32);
            $table->string('to_status', 32);
            $table->date('effective_date');
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->text('reason');
            $table->string('status', 32)->default('requested');
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('requested_at');
            $table->foreignId('decided_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestampsTz();

            $table->index(['student_record_id', 'status']);
            $table->index('status');
        });

        Schema::create('advisor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained()->restrictOnDelete();
            $table->string('role', 32)->default('primary');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestampsTz();

            $table->index('instructor_id');
            $table->index('student_record_id');
        });

        Constraint::translatable('student_status_change_types', 'name');
        DB::statement(<<<'SQL'
            ALTER TABLE student_status_change_types
                ADD CONSTRAINT student_status_change_types_from_statuses_array_check CHECK (jsonb_typeof(from_statuses) = 'array')
            SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE student_records
                ADD CONSTRAINT student_records_year_level_check CHECK (year_level BETWEEN 1 AND 10),
                ADD CONSTRAINT student_records_tags_array_check CHECK (tags IS NULL OR jsonb_typeof(tags) = 'array'),
                ADD CONSTRAINT student_records_graduation_check CHECK (status <> 'graduated' OR graduation_date IS NOT NULL)
            SQL);
        DB::statement('ALTER TABLE student_record_programs ADD CONSTRAINT student_record_programs_period_check CHECK (end_date IS NULL OR end_date >= start_date)');
        DB::statement('ALTER TABLE advisor_assignments ADD CONSTRAINT advisor_assignments_period_check CHECK (end_date IS NULL OR end_date >= start_date)');
        // At most one open primary advisor per student record.
        DB::statement("CREATE UNIQUE INDEX advisor_assignments_single_primary_unique ON advisor_assignments (student_record_id) WHERE role = 'primary' AND end_date IS NULL");
        // At most one pending request per student record.
        DB::statement("CREATE UNIQUE INDEX student_status_changes_single_pending_unique ON student_status_changes (student_record_id) WHERE status = 'requested'");

        Constraint::audit(['student_status_change_types', 'student_records', 'student_record_programs', 'student_status_changes', 'advisor_assignments']);
    }

    public function down(): void
    {
        Schema::dropIfExists('advisor_assignments');
        Schema::dropIfExists('student_status_changes');
        Schema::dropIfExists('student_record_programs');
        Schema::dropIfExists('student_records');
        Schema::dropIfExists('student_number_sequences');
        Schema::dropIfExists('student_status_change_types');
    }
};
