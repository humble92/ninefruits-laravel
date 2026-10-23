<?php

namespace App\Console\Commands\Audit;

use App\Console\Commands\AuditedCommand;
use App\Domain\Security\Actions\RecordSecurityEvent;
use App\Domain\Security\Enums\SecurityEventType;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Support\Facades\DB;

#[Signature('audit:prune {--before= : Delete rows older than this date (YYYY-MM-DD), required} {--table=* : Only these audit tables} {--dry-run : Count only}')]
#[Description('Delete audit rows past the retention period (sealed rows only), on the schema owner\'s connection')]
class PruneAudit extends AuditedCommand
{
    public function __construct(private readonly RecordSecurityEvent $events)
    {
        parent::__construct();
    }

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
            $targets = $connection->table("audit.{$table}")->where($column, '<', $before)->whereNotNull('chain_hash');
            $range = $targets->clone()->selectRaw('count(*) AS rows, min(id) AS first_id, max(id) AS last_id')->first();
            $count = (int) $range->rows;
            $unsealed = (int) $connection->table("audit.{$table}")->where($column, '<', $before)->whereNull('chain_hash')->count();
            // What is being removed, kept in the audit trail of this command: the id range and the
            // chain hash at the boundary, so audit:verify-chain can be reconciled with this pruning.
            $summary[$table] = [
                'rows' => $count, 'first_id' => $range->first_id, 'last_id' => $range->last_id,
                'boundary_chain_hash' => $count > 0 ? $connection->table("audit.{$table}")->where('id', $range->last_id)->value('chain_hash') : null,
            ];

            if (! $this->isDryRun() && $count > 0) {
                $connection->transaction(function () use ($connection, $table, $column, $before) {
                    $connection->statement("SELECT set_config('app.audit_maintenance', 'on', true)");
                    $connection->table("audit.{$table}")->where($column, '<', $before)->whereNotNull('chain_hash')->delete();
                });
                $this->events->handle(SecurityEventType::AuditPruned, $this->actor(), null, null, ['table' => "audit.{$table}", 'before' => $before->toDateString(), ...$summary[$table]]);
            }

            $this->components->twoColumnDetail("audit.{$table}", sprintf('%s%d row(s)%s%s', $this->isDryRun() ? 'would delete ' : 'deleted ', $count,
                $count > 0 ? " (ids {$range->first_id}–{$range->last_id})" : '', $unsealed > 0 ? ", {$unsealed} unsealed row(s) kept (run audit:seal first)" : ''));
        }

        $this->recordResult(['dry_run' => $this->isDryRun(), ...$summary]);

        return self::SUCCESS;
    }
}
