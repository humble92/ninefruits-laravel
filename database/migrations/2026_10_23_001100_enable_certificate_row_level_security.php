<?php

use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Row level security for certificate issues (policies in database/sql/policies/certificates.sql).
 */
return new class extends Migration
{
    public function up(): void
    {
        SqlFile::apply('policies/certificates.sql');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE certificate_issues DISABLE ROW LEVEL SECURITY');
    }
};
