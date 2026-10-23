<?php

use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tamper evidence for the audit tables: a content hash per row at insert and a chain
 * sealed nightly by audit:seal (docs/manual/ko/operations/audit-and-maintenance.md).
 */
return new class extends Migration
{
    public const array TABLES = ['audit.record_changes', 'audit.activity_log', 'audit.security_events'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->char('row_hash', 64)->nullable();
                $blueprint->char('chain_hash', 64)->nullable();
                $blueprint->timestampTz('sealed_at')->nullable();
            });
        }

        SqlFile::apply('triggers/audit_chain.sql');

        foreach (self::TABLES as $table) {
            $name = str_replace('audit.', '', $table);
            DB::statement("CREATE TRIGGER {$name}_before_insert_row_hash BEFORE INSERT ON {$table} FOR EACH ROW EXECUTE FUNCTION audit.set_row_hash()");
            // Existing rows get their hash now; the chain is sealed by the command.
            DB::statement("SELECT set_config('app.audit_maintenance', 'on', true)");
            DB::statement("UPDATE {$table} AS t SET row_hash = audit.row_hash_of(to_jsonb(t)) WHERE row_hash IS NULL");
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            $name = str_replace('audit.', '', $table);
            DB::statement("DROP TRIGGER IF EXISTS {$name}_before_insert_row_hash ON {$table}");
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn(['row_hash', 'chain_hash', 'sealed_at']);
            });
        }

        DB::statement('DROP FUNCTION IF EXISTS audit.verify_chain(regclass)');
        DB::statement('DROP FUNCTION IF EXISTS audit.seal(regclass)');
        DB::statement('DROP FUNCTION IF EXISTS audit.set_row_hash()');
        DB::statement('DROP FUNCTION IF EXISTS audit.row_hash_of(jsonb)');
    }
};
