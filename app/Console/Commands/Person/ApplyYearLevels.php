<?php

namespace App\Console\Commands\Person;

use App\Console\Commands\AuditedCommand;
use App\Domain\Person\Actions\ManageYearLevels;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('students:apply-year-levels')]
#[Description('Apply due approved student year-level changes')]
class ApplyYearLevels extends AuditedCommand
{
    public function handle(ManageYearLevels $levels): int
    {
        $count = $levels->applyDue();
        $this->recordResult(['runs_applied' => $count]);
        $this->components->info("Applied {$count} year-level run(s).");

        return self::SUCCESS;
    }
}
