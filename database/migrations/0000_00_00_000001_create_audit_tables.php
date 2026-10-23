<?php

use App\Support\Database\Identifier;
use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Three audit layers (porting/porting-plan.md section 8):
 * - audit.activity_log: business audit written by spatie/laravel-activitylog
 * - audit.record_changes: data audit written only by database triggers
 * - audit.security_events: authentication and security events
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit.record_changes', function (Blueprint $table) {
            $table->id();
            $table->timestampTz('occurred_at')->useCurrent();
            $table->bigInteger('transaction_id')->default(DB::raw('txid_current()'));
            $table->string('table_schema');
            $table->string('table_name');
            $table->string('operation', 16);
            $table->string('record_id')->nullable();
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->jsonb('changed_columns')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('actor')->nullable();
            $table->string('request_id')->nullable();
            $table->string('database_user');

            $table->index(['table_name', 'record_id']);
            $table->index('user_id');
            $table->index('occurred_at');
        });

        Schema::create('audit.activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->nullableMorphs('subject', 'activity_log_subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'activity_log_causer');
            $table->jsonb('attribute_changes')->nullable();
            $table->jsonb('properties')->nullable();
            $table->text('reason')->nullable();
            $table->string('request_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampsTz();
        });

        Schema::create('audit.security_events', function (Blueprint $table) {
            $table->id();
            $table->timestampTz('occurred_at')->useCurrent()->index();
            $table->string('type')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('identifier')->nullable();
            $table->nullableMorphs('subject', 'security_events_subject');
            $table->jsonb('properties')->nullable();
            $table->string('request_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
        });

        SqlFile::apply('triggers/audit.sql');

        $runtime = Identifier::runtimeRole();

        // record_changes: read only for the application. Rows come from the SECURITY DEFINER trigger.
        // activity_log, security_events: append only.
        DB::statement("REVOKE INSERT, UPDATE, DELETE, TRUNCATE ON audit.record_changes FROM {$runtime}");
        DB::statement("GRANT SELECT, INSERT ON audit.activity_log, audit.security_events TO {$runtime}");
        DB::statement("GRANT USAGE ON SEQUENCE audit.activity_log_id_seq, audit.security_events_id_seq TO {$runtime}");

        foreach (['record_changes', 'activity_log', 'security_events'] as $table) {
            DB::statement(<<<SQL
                CREATE TRIGGER {$table}_before_change_prevent_modification
                BEFORE UPDATE OR DELETE OR TRUNCATE ON audit.{$table}
                FOR EACH STATEMENT EXECUTE FUNCTION audit.prevent_modification()
                SQL);
        }
    }

    public function down(): void
    {
        // Audit history is never dropped by a rollback.
    }
};
