<?php

namespace App\Domain\Registration\Actions;

use App\Domain\Registration\Data\EnrollOptions;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\EnrollmentSource;
use App\Domain\Registration\Enums\WaitlistStatus;
use App\Domain\Registration\Notifications\WaitlistOutcomeNotification;
use App\Domain\Registration\Services\SeatAllocator;
use App\Domain\Term\Enums\TermWindowType;
use App\Domain\Term\Services\TermResolver;
use App\Models\Offering\Section;
use App\Models\Registration\WaitlistEntry;
use Carbon\CarbonImmutable;

/**
 * Gives free seats to waiting students in request order (docs/porting-plan.md section
 * 10.7). Each promotion is a full registration: every rule is checked again, so a student
 * who no longer qualifies (credit limit reached meanwhile, time conflict, hold) is taken
 * off the list with the reason, and the next one is tried. A student who only lacks a
 * seat they qualify for (the rest is reserved) keeps waiting. When neither registration
 * nor add/drop is open for the section any more, the remaining entries expire.
 * Students are notified by email either way.
 */
class PromoteWaitlist
{
    public function __construct(
        private readonly EnrollStudent $enroll,
        private readonly SeatAllocator $seats,
        private readonly TermResolver $terms,
    ) {}

    /**
     * @return array{promoted: int, removed: int, expired: int}
     */
    public function handle(Section $section): array
    {
        $counts = ['promoted' => 0, 'removed' => 0, 'expired' => 0];
        $now = CarbonImmutable::now();

        if (! $this->enrollmentOpen($section, $now)) {
            foreach ($this->waiting($section) as $entry) {
                $this->resolve($entry, WaitlistStatus::Expired, __('registration.waitlist.window_closed'));
                $counts['expired']++;
            }

            return $counts;
        }

        foreach ($this->waiting($section) as $entry) {
            $availability = $this->seats->availability($section->refresh(), $now);

            if ($availability['general'] === 0 && $availability['reserved'] === 0) {
                break;
            }

            $outcome = $this->enroll->handle($entry->studentRecord, $section, null, new EnrollOptions(source: EnrollmentSource::Waitlist), AttemptAction::Promote);

            if ($outcome->succeeded()) {
                $counts['promoted']++;
                $this->notify($entry->refresh(), WaitlistStatus::Promoted, null);
            } elseif (! $outcome->refusedForSeat()) {
                $this->resolve($entry, WaitlistStatus::Removed, implode(' ', $outcome->messages()));
                $counts['removed']++;
            }
        }

        return $counts;
    }

    /**
     * @return iterable<WaitlistEntry>
     */
    private function waiting(Section $section): iterable
    {
        return WaitlistEntry::query()->where('section_id', $section->id)->where('status', WaitlistStatus::Waiting)
            ->orderBy('requested_at')->orderBy('id')->with('studentRecord.person.user')->get();
    }

    private function enrollmentOpen(Section $section, CarbonImmutable $at): bool
    {
        return $this->terms->openWindows(TermWindowType::Registration, $at)->contains('term_id', $section->term_id)
            || $this->terms->openWindows(TermWindowType::AddDrop, $at)->contains('term_session_id', $section->term_session_id);
    }

    private function resolve(WaitlistEntry $entry, WaitlistStatus $status, string $note): void
    {
        $entry->forceFill(['status' => $status, 'resolved_at' => CarbonImmutable::now(), 'outcome_note' => $note])->save();
        $this->notify($entry, $status, $note);
    }

    private function notify(WaitlistEntry $entry, WaitlistStatus $status, ?string $note): void
    {
        $entry->studentRecord->person->user?->notify(new WaitlistOutcomeNotification($entry, $status, $note));
    }
}
