<?php

use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The reporting views (docs/decisions/0016-graduation-and-reporting.md). Re-run this file
 * when a view changes: CREATE OR REPLACE keeps the grants.
 */
return new class extends Migration
{
    private const array VIEWS = ['students', 'headcount', 'term_records', 'course_grades', 'section_enrollments', 'section_meetings', 'status_changes', 'degree_audits'];

    public function up(): void
    {
        SqlFile::apply('views/reporting.sql');
    }

    public function down(): void
    {
        foreach (self::VIEWS as $view) {
            DB::statement("DROP VIEW IF EXISTS reporting.{$view}");
        }
    }
};
