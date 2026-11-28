<?php

use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        SqlFile::apply('person/year_level_progression.sql');
    }

    public function down(): void
    {
        Schema::dropIfExists('year_level_changes');
        Schema::dropIfExists('year_level_runs');
        DB::statement('ALTER TABLE programs DROP COLUMN year_level_rule_id');
        Schema::dropIfExists('year_level_rules');
    }
};
