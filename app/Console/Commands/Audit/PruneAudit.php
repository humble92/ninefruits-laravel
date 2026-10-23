<?php

namespace App\Console\Commands\Audit;

use App\Console\Commands\AuditedCommand;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Support\Facades\DB;

#[Signature('audit:prune {--before= : Delete rows older than this date (YYYY-MM-DD), required} {--table=* : Only these audit tables} {--dry-run : Count only}')]
#[Description('Delete audit rows past the retention period (sealed rows only), on the schema owner\'s connection')]
class PruneAudit extends AuditedCommand
{
    private const array TIME_COLUMNS = ['record_changes' => 'occurred_at', 'activity_log' => 'created_at', 'security_events' => 'created_at'];

    public function handle(): int
    {
        $before = $this->option('before') ? CarbonImmutable::parse((string) $this->option('before')) : null;

        if ($before === null) {
            $this->components->error('--before=YYYY-MM-DD is required.');

            return self::FAILURE;
        }

        $connection = DB::connection((string) config('database.migrator_connection'));
        $summary = ['before' => $before->toDateString()];

        foreach (SealAuditChain::tables($this->option('table')) as $table) {
            $column = self::TIME_COLUMNS[$table];
            $count = (int) $connection->table("audit.{$table}")->where($column, '<', $before)->whereNotNull('chain_hash')->count();
            $unsealed = (int) $connection->table("audit.{$table}")->where($column, '<', $before)->whereNull('chain_hash')->count();

            if (! $this->isDryRun() && $count > 0) {
                $connection->transaction(function () use ($connection, $table, $column, $before) {
                    $connection->statement("SELECT set_config('app.audit_maintenance', 'on', true)");
                    $connection->table("audit.{$table}")->where($column, '<', $before)->whereNotNull('chain_hash')->delete();
                });
            }

            $summary[$table] = $count;
            $this->components->twoColumnDetail("audit.{$table}", sprintf('%s%d row(s)%s', $this->isDryRun() ? 'would delete ' : 'deleted ', $count, $unsealed > 0 ? ", {$unsealed} unsealed row(s) kept (run audit:seal first)" : ''));
        }

        $this->recordResult($summary);

        return self::SUCCESS;
    }
}
