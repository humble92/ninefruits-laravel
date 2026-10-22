<?php

use App\Support\Database\Constraint;
use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sections (legacy SFOPOBJECT): a course version offered in a term session, with
 * instructors, meeting times and rooms, eligibility, seat groups and a syllabus. Rooms and
 * instructors cannot be double-booked (exclusion constraint and triggers,
 * database/sql/functions/sections.sql).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained()->restrictOnDelete();
            $table->bigInteger('term_session_id');
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->bigInteger('course_version_id');
            $table->string('section_code', 16);
            // Offering unit, for scoped staff access.
            $table->foreignId('org_unit_id')->constrained()->restrictOnDelete();
            $table->integer('capacity');
            $table->string('status', 16)->default('draft');
            // Who may register (include) and who may not (exclude), as on term windows.
            $table->jsonb('eligibility')->nullable();
            $table->jsonb('exclusion')->nullable();
            $table->string('instruction_language', 16)->nullable();
            $table->jsonb('note')->nullable();
            $table->foreignId('copied_from_section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['term_id', 'course_id', 'section_code']);
            $table->index(['term_id', 'org_unit_id']);
            $table->index('course_version_id');
        });
        DB::statement('ALTER TABLE sections ADD CONSTRAINT sections_term_session_id_foreign FOREIGN KEY (term_session_id, term_id) REFERENCES term_sessions (id, term_id) ON DELETE RESTRICT');
        DB::statement('ALTER TABLE sections ADD CONSTRAINT sections_course_version_id_foreign FOREIGN KEY (course_version_id, course_id) REFERENCES course_versions (id, course_id) ON DELETE RESTRICT');

        Schema::create('section_instructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained()->restrictOnDelete();
            $table->string('role', 16)->default('primary');
            $table->timestampsTz();

            $table->unique(['section_id', 'instructor_id']);
            $table->index('instructor_id');
        });

        Schema::create('section_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            // ISO-8601 weekday, 1 = Monday.
            $table->smallInteger('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('room_id')->nullable()->constrained()->restrictOnDelete();
            // Normally the session's dates; shorter for a meeting pattern that changes mid-session.
            $table->date('start_date');
            $table->date('end_date');
            // False while the section is cancelled (kept by a trigger on sections).
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->index('section_id');
            $table->index('room_id');
        });

        Schema::create('section_seat_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->jsonb('name');
            $table->integer('capacity');
            $table->jsonb('eligibility')->nullable();
            // After this time unused seats of the group become open to everyone.
            $table->timestampTz('releases_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->index('section_id');
        });

        Schema::create('syllabi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('language', 16);
            $table->jsonb('content');
            $table->timestampTz('published_at')->nullable();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        Constraint::translatable('sections', 'note', nullable: true);
        Constraint::translatable('section_seat_groups', 'name');

        DB::statement(<<<'SQL'
            ALTER TABLE sections
                ADD CONSTRAINT sections_capacity_check CHECK (capacity >= 0),
                ADD CONSTRAINT sections_status_check CHECK (status IN ('draft', 'published', 'cancelled'))
            SQL);
        DB::statement('ALTER TABLE section_instructors ADD CONSTRAINT section_instructors_role_check CHECK (role IN (\'primary\', \'co_instructor\', \'assistant\'))');
        DB::statement(<<<'SQL'
            ALTER TABLE section_meetings
                ADD COLUMN time_range int4range GENERATED ALWAYS AS (int4range(
                    (EXTRACT(HOUR FROM start_time) * 60 + EXTRACT(MINUTE FROM start_time))::int,
                    (EXTRACT(HOUR FROM end_time) * 60 + EXTRACT(MINUTE FROM end_time))::int
                )) STORED,
                ADD COLUMN date_range daterange GENERATED ALWAYS AS (daterange(start_date, end_date, '[]')) STORED,
                ADD CONSTRAINT section_meetings_weekday_check CHECK (weekday BETWEEN 1 AND 7),
                ADD CONSTRAINT section_meetings_time_check CHECK (end_time > start_time),
                ADD CONSTRAINT section_meetings_date_check CHECK (end_date >= start_date),
                ADD CONSTRAINT section_meetings_room_excl EXCLUDE USING gist (room_id WITH =, weekday WITH =, time_range WITH &&, date_range WITH &&)
                    WHERE (room_id IS NOT NULL AND is_active),
                ADD CONSTRAINT section_meetings_section_excl EXCLUDE USING gist (section_id WITH =, weekday WITH =, time_range WITH &&, date_range WITH &&)
            SQL);
        DB::statement('ALTER TABLE section_seat_groups ADD CONSTRAINT section_seat_groups_capacity_check CHECK (capacity >= 0)');
        DB::statement("ALTER TABLE syllabi ADD CONSTRAINT syllabi_content_object_check CHECK (jsonb_typeof(content) = 'object')");

        SqlFile::apply('functions/sections.sql');
        DB::statement(<<<'SQL'
            CREATE TRIGGER section_meetings_after_write_check_instructors
                AFTER INSERT OR UPDATE OF section_id, weekday, start_time, end_time, start_date, end_date, is_active ON section_meetings
                FOR EACH ROW EXECUTE FUNCTION util.section_meetings_check_instructors()
            SQL);
        DB::statement(<<<'SQL'
            CREATE TRIGGER section_instructors_after_write_check_meetings
                AFTER INSERT OR UPDATE OF section_id, instructor_id ON section_instructors
                FOR EACH ROW EXECUTE FUNCTION util.section_instructors_check_meetings()
            SQL);
        DB::statement(<<<'SQL'
            CREATE TRIGGER sections_after_update_sync_meetings
                AFTER UPDATE OF status ON sections
                FOR EACH ROW WHEN (OLD.status IS DISTINCT FROM NEW.status) EXECUTE FUNCTION util.sections_sync_meeting_activity()
            SQL);

        Constraint::audit(['sections', 'section_instructors', 'section_meetings', 'section_seat_groups', 'syllabi']);
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabi');
        Schema::dropIfExists('section_seat_groups');
        Schema::dropIfExists('section_meetings');
        Schema::dropIfExists('section_instructors');
        Schema::dropIfExists('sections');
    }
};
