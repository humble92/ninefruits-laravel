<?php

use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Absolute and relative evaluation (decided 2026-10-22). An absolutely evaluated section
 * takes a score per student; the grade follows from it on the institution's scale (the
 * default scale, 4.3 or 4.5), and the grade on the other scale is kept beside it, so a later
 * change of scale has both values. A relatively evaluated section takes grades only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->string('evaluation_method', 16)->default('relative');
        });
        DB::statement("ALTER TABLE sections ADD CONSTRAINT sections_evaluation_method_check CHECK (evaluation_method IN ('absolute', 'relative'))");

        Schema::table('enrollments', function (Blueprint $table) {
            // The same score on the comparison scale (absolute evaluation only).
            $table->foreignId('comparison_grade_scale_id')->nullable()->constrained('grade_scales')->restrictOnDelete();
            $table->string('comparison_grade_code', 8)->nullable();
            $table->decimal('comparison_grade_points', 4, 2)->nullable();
        });
        DB::statement(<<<'SQL'
            ALTER TABLE enrollments ADD CONSTRAINT enrollments_comparison_grade_check CHECK (
                (comparison_grade_code IS NULL) = (comparison_grade_scale_id IS NULL)
                AND (comparison_grade_code IS NULL OR score IS NOT NULL)
            )
            SQL);

        SqlFile::apply('triggers/grades.sql');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE enrollments DROP CONSTRAINT IF EXISTS enrollments_comparison_grade_check');
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('comparison_grade_scale_id');
            $table->dropColumn(['comparison_grade_code', 'comparison_grade_points']);
        });
        DB::statement('ALTER TABLE sections DROP CONSTRAINT IF EXISTS sections_evaluation_method_check');
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('evaluation_method');
        });
    }
};
