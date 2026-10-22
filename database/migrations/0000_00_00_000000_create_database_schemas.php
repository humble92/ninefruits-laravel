<?php

use App\Support\Database\Identifier;
use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Schemas, extensions and default grants. Runs first so that every later table
 * receives the default privileges for the runtime role.
 *
 * Requires the roles from database/sql/roles/create_roles.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        $runtime = Identifier::runtimeRole();
        $reporting = Identifier::reportingRole();

        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        foreach (['audit', 'reporting', 'util'] as $schema) {
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$schema}");
        }

        DB::statement("GRANT USAGE ON SCHEMA public TO {$runtime}");
        DB::statement("GRANT USAGE ON SCHEMA util TO {$runtime}, {$reporting}");
        DB::statement("GRANT USAGE ON SCHEMA audit TO {$runtime}");
        DB::statement("GRANT USAGE ON SCHEMA reporting TO {$runtime}, {$reporting}");

        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO {$runtime}");
        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO {$runtime}");
        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA reporting GRANT SELECT ON TABLES TO {$runtime}, {$reporting}");

        // Audit tables: grants are given per table (append-only, see create_audit_tables).
        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA audit GRANT SELECT ON TABLES TO {$runtime}");

        SqlFile::apply('functions/session_context.sql');
        SqlFile::apply('functions/i18n.sql');
    }

    public function down(): void
    {
        // Schemas hold audit history and are never dropped by a rollback.
    }
};
