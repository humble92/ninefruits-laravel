<?php

namespace App\Domain\Registration\Data;

use App\Domain\Registration\Enums\AttemptOutcome;
use App\Domain\Registration\Enums\RuleCode;
use App\Models\Registration\Enrollment;
use App\Models\Registration\WaitlistEntry;

/**
 * Result of an enroll attempt: enrolled, on the waitlist, or refused (with every reason).
 */
final readonly class RegistrationOutcome
{
    public function __construct(
        public AttemptOutcome $outcome,
        public RuleEvaluation $evaluation,
        public ?Enrollment $enrollment = null,
        public ?WaitlistEntry $waitlistEntry = null,
    ) {}

    public function succeeded(): bool
    {
        return $this->outcome === AttemptOutcome::Succeeded;
    }

    /**
     * Refused only because no seat was free for this student.
     */
    public function refusedForSeat(): bool
    {
        if ($this->outcome !== AttemptOutcome::Refused) {
            return false;
        }

        $blocking = array_filter($this->evaluation->results, fn (RuleResult $result) => $result->blocks());

        return $blocking !== [] && array_values($blocking)[0]->code === RuleCode::Capacity && count($blocking) === 1;
    }

    /**
     * @return list<string>
     */
    public function messages(): array
    {
        return $this->outcome === AttemptOutcome::Refused ? $this->evaluation->blockingMessages() : $this->evaluation->warnings();
    }
}
