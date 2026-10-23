<?php

use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Row level security for course evaluation (policies in database/sql/policies/evaluations.sql).
 * Forms, questions and term settings are configuration without personal data.
 */
return new class extends Migration
{
    public function up(): void
    {
        SqlFile::apply('policies/evaluations.sql');
    }

    public function down(): void
    {
        foreach (['evaluation_completions', 'evaluation_responses', 'evaluation_answers'] as $table) {
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
