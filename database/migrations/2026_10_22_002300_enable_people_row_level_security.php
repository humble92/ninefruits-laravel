<?php

use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Row level security on people, guardians, student records and status changes
 * (porting/porting-plan.md section 7.4, policies in database/sql/policies).
 */
return new class extends Migration
{
    public function up(): void
    {
        SqlFile::apply('functions/people.sql');

        // Creator of a person row (database default from the request context). Lets the
        // creator read the row back before it has a student record (INSERT ... RETURNING).
        DB::statement('ALTER TABLE people ADD COLUMN created_by_id bigint DEFAULT util.current_user_id() REFERENCES users (id) ON DELETE SET NULL');

        SqlFile::apply('policies/student_records.sql');
        SqlFile::apply('policies/people.sql');
    }

    public function down(): void
    {
        foreach (['people', 'guardians', 'student_records', 'student_status_changes'] as $table) {
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
