<?php

namespace App\Console\Commands\Lms;

use App\Console\Commands\AuditedCommand;
use App\Domain\Lms\Actions\ReconcilePlatform;
use App\Models\Lms\LmsPlatform;
use App\Models\Term\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('lms:reconcile {platform? : Platform code (default: every active platform)} {--term= : Term code} {--dry-run : Report differences only}')]
#[Description('Compare the rosters on an LMS platform with the academic system and fix the differences (nightly)')]
class ReconcileLms extends AuditedCommand
{
    public function handle(ReconcilePlatform $reconcile): int
    {
        $platforms = $this->argument('platform')
            ? LmsPlatform::query()->where('code', (string) $this->argument('platform'))->get()
            : LmsPlatform::query()->where('is_active', true)->get();

        if ($platforms->isEmpty()) {
            $this->components->error($this->argument('platform') ? 'Unknown platform code.' : 'No active platform.');

            return self::FAILURE;
        }

        $term = null;

        if ($this->option('term')) {
            $term = Term::query()->where('code', (string) $this->option('term'))->first();

            if ($term === null) {
                $this->components->error('Unknown term code.');

                return self::FAILURE;
            }
        }

        $failed = 0;

        foreach ($platforms as $platform) {
            $summary = $reconcile->handle($platform, $term, $this->isDryRun());
            $failed += $summary['failed'];
            $this->recordResult([$platform->code => $summary]);
            $this->components->twoColumnDetail($platform->code, sprintf('%d section(s): +%d -%d, %d failed', $summary['sections'], $summary['added'], $summary['removed'], $summary['failed']));
        }

        return $failed === 0 ? self::SUCCESS : self::PROBLEMS_FOUND;
    }
}
