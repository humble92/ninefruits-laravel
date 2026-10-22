<?php

namespace App\Domain\Registration\Services;

use App\Domain\Registration\Data\RuleEvaluation;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\AttemptOutcome;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\RegistrationAttempt;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Context;

/**
 * Writes registration_attempts: every attempt with each rule's result.
 */
class AttemptLog
{
    public function record(StudentRecord $record, ?Section $section, AttemptAction $action, AttemptOutcome $outcome, RuleEvaluation $evaluation, ?User $actor): RegistrationAttempt
    {
        return RegistrationAttempt::query()->create([
            'student_record_id' => $record->id,
            'term_id' => $section?->term_id,
            'section_id' => $section?->id,
            'action' => $action,
            'outcome' => $outcome,
            'results' => $evaluation->toArray(),
            'actor_id' => $actor?->id,
            'request_id' => Context::get('request_id'),
            'attempted_at' => CarbonImmutable::now(),
        ]);
    }
}
