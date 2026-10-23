<?php

namespace App\Console\Commands\Audit;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('audit:verify-chain {--table=* : Only these audit tables}')]
#[Description('Recompute every audit row hash and the sealed chain; exit 2 when a row was changed, removed or inserted out of order')]
class VerifyAuditChain extends Command
{
    public function handle(): int
    {
        $problems = 0;

        foreach (SealAuditChain::tables($this->option('table')) as $table) {
            $result = DB::selectOne('SELECT * FROM audit.verify_chain(?::regclass)', ["audit.{$table}"]);
            $bad = (int) $result->row_mismatches + (int) $result->chain_mismatches;
            $problems += $bad;
            $this->components->twoColumnDetail("audit.{$table}", sprintf('%d checked, %d sealed, %d row mismatch(es), %d chain mismatch(es)%s',
                $result->checked, $result->sealed, $result->row_mismatches, $result->chain_mismatches, $result->first_bad_id ? ", first bad id {$result->first_bad_id}" : ''));
        }

        if ($problems > 0) {
            $this->components->error("{$problems} problem(s) found: the audit tables were modified outside the application.");

            return 2;
        }

        $this->components->info('Audit chain intact.');

        return self::SUCCESS;
    }
}
