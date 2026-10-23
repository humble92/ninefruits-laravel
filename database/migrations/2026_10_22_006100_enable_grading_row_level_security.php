<?php

use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Row level security for grades (policies in database/sql/policies/grading.sql).
 */
return new class extends Migration
{
    public function up(): void
    {
        SqlFile::apply('policies/grading.sql');
    }

    public function down(): void
    {
        foreach (['enrollments_grades_staff_select', 'enrollments_instructor_grade_update', 'enrollments_grades_staff_update'] as $policy) {
            DB::statement("DROP POLICY IF EXISTS {$policy} ON enrollments");
        }

        foreach (['grade_change_requests', 'term_records'] as $table) {
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
