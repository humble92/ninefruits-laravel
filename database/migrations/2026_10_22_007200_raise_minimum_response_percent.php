<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Results need at least 25% of the students to respond, as the legacy academic office list
 * did (decided 2026-10-23); term evaluations still at 0% move up.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE term_evaluations ALTER COLUMN minimum_response_percent SET DEFAULT 25');
        DB::table('term_evaluations')->where('minimum_response_percent', 0)->whereNull('results_published_at')->update(['minimum_response_percent' => 25]);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE term_evaluations ALTER COLUMN minimum_response_percent SET DEFAULT 0');
    }
};
