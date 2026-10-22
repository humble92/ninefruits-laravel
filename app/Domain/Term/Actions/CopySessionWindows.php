<?php

namespace App\Domain\Term\Actions;

use App\Domain\Security\Services\AuditLogger;
use App\Models\Term\TermSession;
use App\Models\Term\TermWindow;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Starts a session's course windows from another session's (normally the full term),
 * shifted to the target dates. A window that opens in the first half of the source
 * session keeps its distance from the session start (add/drop, withdrawal); a later one
 * keeps its distance from the session end (grade entry, evaluation). Staff adjust after.
 */
class CopySessionWindows
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @return int Windows created
     */
    public function handle(TermSession $source, TermSession $target, User $copiedBy): int
    {
        if ($source->is($target) || $source->term_id !== $target->term_id) {
            throw ValidationException::withMessages(['source' => __('reference.errors.invalid_session_copy')]);
        }

        if ($target->windows()->exists()) {
            throw ValidationException::withMessages(['source' => __('reference.errors.session_has_windows')]);
        }

        $sourceStart = $source->start_date->startOfDay();
        $sourceEnd = $source->end_date->startOfDay();
        $midpoint = $sourceStart->addSeconds(intdiv((int) $sourceStart->diffInSeconds($sourceEnd), 2));
        $startShift = (int) $sourceStart->diffInDays($target->start_date->startOfDay(), false);
        $endShift = (int) $sourceEnd->diffInDays($target->end_date->startOfDay(), false);

        return DB::transaction(function () use ($source, $target, $midpoint, $startShift, $endShift, $copiedBy) {
            $count = 0;

            foreach ($source->windows()->get() as $window) {
                $shift = $window->opens_at->lessThan($midpoint) ? $startShift : $endShift;

                TermWindow::query()->create([
                    'term_id' => $target->term_id,
                    'term_session_id' => $target->id,
                    'window_type' => $window->window_type,
                    'name' => $window->getTranslations('name'),
                    'opens_at' => $window->opens_at->addDays($shift),
                    'closes_at' => $window->closes_at->addDays($shift),
                    'eligibility' => $window->eligibility,
                ]);
                $count++;
            }

            $this->audit->record('term_session.windows_copied', $target, null, ['source_session_id' => $source->id, 'windows' => $count], $copiedBy);

            return $count;
        });
    }
}
