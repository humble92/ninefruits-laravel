<?php

use App\Support\Database\Constraint;
use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Attendance (legacy prt_chuxi printed an empty sheet; nothing was recorded): class
 * meetings generated from the section's weekly meeting times, and one attendance record
 * per student per meeting. Also lets a section's instructors see the student records and
 * people of its roster (RLS and the application mirror).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            // The weekly meeting time this was generated from; null for an added meeting (make-up class).
            $table->foreignId('section_meeting_id')->nullable()->constrained()->nullOnDelete();
            $table->date('meeting_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('room_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('status', 16)->default('scheduled');
            $table->string('topic', 200)->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestampsTz();

            $table->unique(['section_id', 'meeting_date', 'start_time']);
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('status', 16);
            $table->string('note', 200)->nullable();
            $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('recorded_at');
            $table->timestampsTz();

            $table->unique(['class_meeting_id', 'enrollment_id']);
            $table->index('enrollment_id');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE class_meetings
                ADD CONSTRAINT class_meetings_status_check CHECK (status IN ('scheduled', 'cancelled')),
                ADD CONSTRAINT class_meetings_time_check CHECK (start_time < end_time),
                ADD CONSTRAINT class_meetings_cancel_check CHECK ((status = 'cancelled') = (cancel_reason IS NOT NULL))
            SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE attendance_records
                ADD CONSTRAINT attendance_records_status_check CHECK (status IN ('present', 'late', 'absent', 'excused'))
            SQL);

        Constraint::audit(['class_meetings', 'attendance_records']);
        SqlFile::apply('functions/attendance.sql');
        SqlFile::apply('policies/attendance.sql');
    }

    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS student_records_instructor_select ON student_records');
        DB::statement('DROP FUNCTION IF EXISTS util.taught_student_record_ids()');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('class_meetings');
    }
};
