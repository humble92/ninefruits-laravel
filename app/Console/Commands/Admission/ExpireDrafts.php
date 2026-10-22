<?php

namespace App\Console\Commands\Admission;

use App\Console\Commands\AuditedCommand;
use App\Domain\Admission\Actions\ExpireDraftApplications;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('admission:expire-drafts {--dry-run : Count the drafts without changing them}')]
#[Description('Expire draft applications of closed cycles or untouched for the configured days (scheduled daily)')]
class ExpireDrafts extends AuditedCommand
{
    public function handle(ExpireDraftApplications $expire): int
    {
        if ($this->isDryRun()) {
            $count = $expire->query()->count();
            $this->recordResult(['drafts' => $count]);
            $this->components->info("Dry run: {$count} draft(s) would expire.");

            return self::SUCCESS;
        }

        $count = $expire->handle();
        $this->recordResult(['expired' => $count]);
        $this->components->info("{$count} draft(s) expired.");

        return self::SUCCESS;
    }
}
