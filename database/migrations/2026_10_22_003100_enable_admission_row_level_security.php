<?php

use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Row level security on applications (policies in database/sql/policies/admissions.sql).
 * Cycles, offered programs and document types are public information.
 */
return new class extends Migration
{
    public function up(): void
    {
        SqlFile::apply('policies/admissions.sql');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE applications DISABLE ROW LEVEL SECURITY');
    }
};
