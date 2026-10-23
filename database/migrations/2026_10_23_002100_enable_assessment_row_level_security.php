<?php

use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        SqlFile::apply('policies/assessments.sql');
    }

    public function down(): void
    {
        foreach (['assessment_registrations', 'assessment_results', 'assessment_component_scores'] as $table) {
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
