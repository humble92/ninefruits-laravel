<?php

namespace App\Console\Commands\Registration;

use App\Domain\Registration\Actions\ReleaseReservedSeats;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Not audited as a command (it runs every 5 minutes); the promotions it triggers are in
 * the registration attempt log.
 */
#[Signature('registration:release-reserved-seats')]
#[Description('Hand reserved seats past their release time to the waitlists (scheduled every 5 minutes)')]
class ReleaseReservedSeatsCommand extends Command
{
    public function handle(ReleaseReservedSeats $release): int
    {
        $sections = $release->handle();
        $this->components->info(count($sections).' section(s) with released seats.');

        return self::SUCCESS;
    }
}
