<?php

use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Row level security on registration tables (policies in database/sql/policies/registration.sql).
 * Rule sets and credit limit policies are configuration without personal data.
 */
return new class extends Migration
{
    public function up(): void
    {
        SqlFile::apply('policies/registration.sql');
    }

    public function down(): void
    {
        foreach (['enrollments', 'waitlist_entries', 'cart_items', 'registration_holds', 'registration_overrides', 'registration_attempts'] as $table) {
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
