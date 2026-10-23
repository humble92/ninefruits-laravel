<?php

use App\Support\Database\Constraint;
use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LMS integration base (docs/porting-plan.md section 12, docs/decisions/0017-lms-integration.md):
 * platforms with a driver and credentials, course and user links, and the sync log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->jsonb('name');
            // null (records only), moodle, openedx, ... : the connector that talks to the platform.
            $table->string('driver', 32)->default('null');
            $table->string('base_url', 255)->nullable();
            // Tokens and secrets, encrypted at rest; never shown back.
            $table->text('credentials')->nullable();
            $table->jsonb('options');
            $table->boolean('is_active')->default(false);
            $table->boolean('should_sync_enrollments')->default(true);
            $table->boolean('should_pull_grades')->default(false);
            $table->timestampsTz();
        });

        Schema::create('lms_course_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lms_platform_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('external_course_id', 128)->nullable();
            $table->string('external_url', 512)->nullable();
            $table->string('status', 16)->default('pending');
            $table->timestampTz('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestampsTz();

            $table->unique(['lms_platform_id', 'section_id']);
        });

        Schema::create('lms_user_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lms_platform_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('external_user_id', 128);
            $table->timestampTz('last_synced_at')->nullable();
            $table->timestampsTz();

            $table->unique(['lms_platform_id', 'user_id']);
            $table->unique(['lms_platform_id', 'external_user_id']);
        });

        Schema::create('lms_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lms_platform_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // course.sync, enrollment.add, enrollment.remove, grades.pull, reconcile
            $table->string('operation', 32);
            $table->string('status', 16);
            $table->text('message')->nullable();
            $table->jsonb('payload')->nullable();
            $table->timestampTz('started_at');
            $table->timestampTz('finished_at')->nullable();
            $table->timestampsTz();

            $table->index(['lms_platform_id', 'started_at']);
            $table->index(['section_id', 'started_at']);
        });

        Constraint::translatable('lms_platforms', 'name');

        DB::statement(<<<'SQL'
            ALTER TABLE lms_course_links ADD CONSTRAINT lms_course_links_status_check CHECK (status IN ('pending', 'linked', 'failed'))
            SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE lms_sync_logs ADD CONSTRAINT lms_sync_logs_status_check CHECK (status IN ('queued', 'succeeded', 'failed', 'skipped'))
            SQL);

        Constraint::audit(['lms_course_links', 'lms_user_links']);
        DB::statement("SELECT util.enable_audit('lms_platforms', ARRAY['credentials'])");
        SqlFile::apply('policies/lms.sql');
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_sync_logs');
        Schema::dropIfExists('lms_user_links');
        Schema::dropIfExists('lms_course_links');
        Schema::dropIfExists('lms_platforms');
    }
};
