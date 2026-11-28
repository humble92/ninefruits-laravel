<?php

use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        SqlFile::apply('person/year_level_program_filter.sql');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX year_level_changes_run_program_index');
        DB::statement('ALTER TABLE year_level_runs DROP COLUMN program_id');
    }
};
