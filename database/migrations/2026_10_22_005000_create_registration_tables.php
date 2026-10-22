<?php

use App\Support\Database\Constraint;
use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Registration (legacy SSCORE application rows, SRDENY, SRALLOW, SV_SUBAPP): enrollments
 * with explicit status (docs/porting-plan.md section 10.3), seat counters for atomic seat
 * taking (10.7), waitlists, holds and overrides, rule sets per term and credit limit
 * policies (10.6), carts and the attempt log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            // Maintained by the registration actions under a row lock on the section.
            $table->integer('enrolled_count')->default(0);
            // Students who may wait for a seat; 0: no waitlist.
            $table->integer('waitlist_capacity')->default(0);
        });

        Schema::table('section_seat_groups', function (Blueprint $table) {
            $table->integer('enrolled_count')->default(0);
            // Set when the release has been handled (waitlist promotion for the freed seats).
            $table->timestampTz('release_processed_at')->nullable();
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_record_id')->constrained()->restrictOnDelete();
            $table->foreignId('section_id')->constrained()->restrictOnDelete();
            $table->foreignId('term_id')->constrained()->restrictOnDelete();
            // Snapshot of the course as taken (section 10.3): later revisions change nothing here.
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_version_id')->constrained()->restrictOnDelete();
            $table->decimal('credits', 4, 1);
            $table->foreignId('course_category_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('counts_toward_credit_limit')->default(true);
            $table->string('status', 16)->default('enrolled');
            $table->foreignId('seat_group_id')->nullable()->constrained('section_seat_groups')->nullOnDelete();
            $table->boolean('is_retake')->default(false);
            // The earlier enrollment of the same (or an equivalent) course this one repeats.
            $table->foreignId('repeats_enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->string('source', 16)->default('student');
            $table->timestampTz('enrolled_at');
            $table->timestampTz('dropped_at')->nullable();
            $table->timestampTz('withdrawn_at')->nullable();
            $table->foreignId('changed_by_id')->nullable()->constrained('users')->nullOnDelete();
            // Grades (Phase 6). Present now so requisite and repeat checks have one place to read.
            $table->string('grade_status', 16)->default('none');
            $table->string('grade_code', 8)->nullable();
            $table->boolean('is_passed')->nullable();
            $table->timestampsTz();

            $table->index(['student_record_id', 'term_id']);
            $table->index(['section_id', 'status']);
            $table->index(['student_record_id', 'course_id']);
        });

        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_record_id')->constrained()->cascadeOnDelete();
            $table->string('status', 16)->default('waiting');
            $table->timestampTz('requested_at');
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('resolved_at')->nullable();
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            // Why the entry was removed (rules no longer met, left, window closed).
            $table->text('outcome_note')->nullable();
            $table->timestampsTz();

            $table->index(['section_id', 'status', 'requested_at']);
            $table->index('student_record_id');
        });

        Schema::create('registration_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_record_id')->constrained()->cascadeOnDelete();
            $table->string('hold_type', 32);
            $table->boolean('blocks_registration')->default(true);
            $table->boolean('blocks_grades')->default(false);
            $table->boolean('blocks_certificates')->default(false);
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at')->nullable();
            $table->text('reason');
            $table->string('source', 16)->default('manual');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('released_at')->nullable();
            $table->foreignId('released_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('release_reason')->nullable();
            $table->timestampsTz();

            $table->index('student_record_id');
        });

        Schema::create('registration_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('rule_code', 64);
            // Limits the exception to one section or one course; neither: the whole term.
            $table->foreignId('section_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('reason');
            $table->timestampTz('expires_at')->nullable();
            $table->foreignId('granted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('revoked_at')->nullable();
            $table->foreignId('revoked_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['student_record_id', 'term_id']);
        });

        Schema::create('registration_rule_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('copied_from_rule_set_id')->nullable()->constrained('registration_rule_sets')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestampsTz();
        });

        Schema::create('registration_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_rule_set_id')->constrained()->cascadeOnDelete();
            $table->string('rule_code', 64);
            $table->boolean('is_enabled')->default(true);
            $table->string('severity', 8)->default('block');
            $table->boolean('allows_override')->default(true);
            $table->jsonb('parameters')->nullable();
            $table->timestampsTz();

            $table->unique(['registration_rule_set_id', 'rule_code']);
        });

        Schema::create('credit_limit_policies', function (Blueprint $table) {
            $table->id();
            $table->jsonb('name');
            // Null: every term type.
            $table->foreignId('term_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->jsonb('eligibility')->nullable();
            $table->decimal('min_credits', 4, 1)->nullable();
            $table->decimal('max_credits', 4, 1);
            // The matching policy with the highest priority applies.
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->timestampsTz();

            $table->unique(['student_record_id', 'section_id']);
        });

        Schema::create('registration_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 16);
            $table->string('outcome', 16);
            // Every rule result: [{code, passed, severity, overridden, message}]
            $table->jsonb('results');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('request_id')->nullable();
            $table->timestampTz('attempted_at');

            $table->index(['student_record_id', 'attempted_at']);
            $table->index(['section_id', 'attempted_at']);
        });

        Constraint::translatable('credit_limit_policies', 'name');

        DB::statement(<<<'SQL'
            ALTER TABLE sections
                ADD CONSTRAINT sections_enrolled_count_check CHECK (enrolled_count >= 0 AND enrolled_count <= capacity),
                ADD CONSTRAINT sections_waitlist_capacity_check CHECK (waitlist_capacity >= 0)
            SQL);
        DB::statement('ALTER TABLE section_seat_groups ADD CONSTRAINT section_seat_groups_enrolled_count_check CHECK (enrolled_count >= 0 AND enrolled_count <= capacity)');
        DB::statement(<<<'SQL'
            ALTER TABLE enrollments
                ADD CONSTRAINT enrollments_status_check CHECK (status IN ('enrolled', 'dropped', 'withdrawn', 'completed')),
                ADD CONSTRAINT enrollments_grade_status_check CHECK (grade_status IN ('none', 'draft', 'submitted', 'finalized')),
                ADD CONSTRAINT enrollments_source_check CHECK (source IN ('student', 'staff', 'waitlist', 'import'))
            SQL);
        // One active enrollment per section, and per course in a term (any section).
        DB::statement("CREATE UNIQUE INDEX enrollments_active_section_unique ON enrollments (student_record_id, section_id) WHERE status IN ('enrolled', 'completed')");
        DB::statement("CREATE UNIQUE INDEX enrollments_active_course_unique ON enrollments (student_record_id, term_id, course_id) WHERE status = 'enrolled'");
        DB::statement("ALTER TABLE waitlist_entries ADD CONSTRAINT waitlist_entries_status_check CHECK (status IN ('waiting', 'promoted', 'removed', 'expired'))");
        DB::statement("CREATE UNIQUE INDEX waitlist_entries_single_waiting_unique ON waitlist_entries (section_id, student_record_id) WHERE status = 'waiting'");
        DB::statement(<<<'SQL'
            ALTER TABLE registration_holds
                ADD CONSTRAINT registration_holds_type_check CHECK (hold_type IN ('unregistered', 'financial', 'academic', 'administrative', 'other')),
                ADD CONSTRAINT registration_holds_source_check CHECK (source IN ('manual', 'import', 'system')),
                ADD CONSTRAINT registration_holds_period_check CHECK (ends_at IS NULL OR ends_at > starts_at)
            SQL);
        DB::statement("ALTER TABLE registration_rules ADD CONSTRAINT registration_rules_severity_check CHECK (severity IN ('block', 'warn'))");
        DB::statement("ALTER TABLE registration_rules ADD CONSTRAINT registration_rules_parameters_check CHECK (parameters IS NULL OR jsonb_typeof(parameters) = 'object')");
        DB::statement(<<<'SQL'
            ALTER TABLE credit_limit_policies
                ADD CONSTRAINT credit_limit_policies_credits_check CHECK (max_credits >= 0 AND (min_credits IS NULL OR min_credits <= max_credits))
            SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE registration_attempts
                ADD CONSTRAINT registration_attempts_action_check CHECK (action IN ('enroll', 'drop', 'withdraw', 'waitlist', 'promote', 'cart')),
                ADD CONSTRAINT registration_attempts_outcome_check CHECK (outcome IN ('succeeded', 'waitlisted', 'refused'))
            SQL);

        // Seat counters change on every registration: re-enable the audit of sections and
        // seat groups so that counter-only updates are not recorded (other changes are).
        SqlFile::apply('triggers/audit.sql');
        DB::select("SELECT util.enable_audit('sections', '{}', ARRAY['enrolled_count', 'updated_at'])");
        DB::select("SELECT util.enable_audit('section_seat_groups', '{}', ARRAY['enrolled_count', 'release_processed_at', 'updated_at'])");

        // The attempt log is the record of itself: no trigger audit (high volume).
        Constraint::audit(['enrollments', 'waitlist_entries', 'registration_holds', 'registration_overrides', 'registration_rule_sets', 'registration_rules', 'credit_limit_policies']);
    }

    public function down(): void
    {
        foreach (['registration_attempts', 'cart_items', 'credit_limit_policies', 'registration_rules', 'registration_rule_sets', 'registration_overrides', 'registration_holds', 'waitlist_entries', 'enrollments'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('section_seat_groups', fn (Blueprint $table) => $table->dropColumn(['enrolled_count', 'release_processed_at']));
        Schema::table('sections', fn (Blueprint $table) => $table->dropColumn(['enrolled_count', 'waitlist_capacity']));
    }
};
