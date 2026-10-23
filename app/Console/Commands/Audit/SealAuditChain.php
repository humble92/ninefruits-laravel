<?php

namespace App\Console\Commands\Audit;

use App\Console\Commands\AuditedCommand;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Support\Facades\DB;

#[Signature('audit:seal {--table=* : Only these audit tables (record_changes, activity_log, security_events)}')]
#[Description('Link new audit rows into the tamper-evidence hash chain (nightly)')]
class SealAuditChain extends AuditedCommand
{
    public const array TABLES = ['record_changes', 'activity_log', 'security_events'];

    public function handle(): int
    {
        $connection = DB::connection((string) config('database.migrator_connection'));
        $summary = [];

        foreach (self::tables($this->option('table')) as $table) {
            $sealed = (int) $connection->selectOne('SELECT audit.seal(?::regclass) AS sealed', ["audit.{$table}"])->sealed;
            $summary[$table] = $sealed;
            $this->components->twoColumnDetail("audit.{$table}", "{$sealed} row(s) sealed");
        }

        $this->recordResult($summary);

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $only
     * @return list<string>
     */
    public static function tables(array $only): array
    {
        return $only === [] ? self::TABLES : array_values(array_intersect(self::TABLES, $only));
    }
}
