<?php

namespace App\Console\Commands\Term;

use App\Domain\Term\Enums\TermWindowType;
use App\Domain\Term\Services\TermResolver;
use App\Models\Term\TermWindow;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('term:inspect {--at= : Point in time to check (default now), e.g. "2027-02-15 09:00"}')]
#[Description('Show the terms in session and the open business windows at a point in time (TermResolver check)')]
class InspectTerms extends Command
{
    public function handle(TermResolver $resolver): int
    {
        $at = $this->option('at') ? CarbonImmutable::parse((string) $this->option('at')) : CarbonImmutable::now();

        $this->components->twoColumnDetail('Time', $at->toIso8601String().' (institution date '.$resolver->localDate($at).')');

        $inSession = $resolver->inSession($at);
        $this->components->twoColumnDetail('In session', $inSession->isEmpty() ? '-' : $inSession->map(fn ($term) => $term->code)->implode(', '));

        $rows = [];

        foreach (TermWindowType::cases() as $type) {
            foreach ($resolver->openWindows($type, $at) as $window) {
                /** @var TermWindow $window */
                $rows[] = [
                    $type->value,
                    $window->term->code,
                    $window->name ?? '',
                    $window->eligibility->isEveryone() ? 'everyone' : json_encode($window->eligibility->toArray()),
                    $window->closes_at->setTimezone($resolver->timezone())->format('Y-m-d H:i').' '.$resolver->timezone(),
                ];
            }
        }

        $this->newLine();

        if ($rows === []) {
            $this->components->info('No business window is open.');
        } else {
            $this->table(['Window', 'Term', 'Name', 'Eligibility', 'Closes'], $rows);
        }

        return self::SUCCESS;
    }
}
