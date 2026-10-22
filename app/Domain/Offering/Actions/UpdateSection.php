<?php

namespace App\Domain\Offering\Actions;

use App\Domain\Offering\Services\ScheduleConflicts;
use App\Models\Offering\Section;
use App\Models\Offering\SectionMeeting;
use App\Models\Term\TermSession;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/**
 * Edits a section. Moving it to another session of the term takes the course version of
 * the new session start and moves meetings that followed the old session's dates.
 */
class UpdateSection
{
    public function __construct(private readonly ScheduleConflicts $conflicts) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Section $section, array $data, User $updatedBy): Section
    {
        if (! $section->isManageableBy($updatedBy)) {
            throw new AuthorizationException;
        }

        $sessionId = (int) ($data['term_session_id'] ?? $section->term_session_id);

        try {
            return $this->conflicts->guard(function () use ($section, $data, $sessionId) {
                if ($sessionId !== $section->term_session_id) {
                    $old = $section->session;
                    $new = TermSession::query()->where('term_id', $section->term_id)->findOrFail($sessionId);

                    $section->forceFill([
                        'term_session_id' => $new->id,
                        'course_version_id' => CreateSection::versionFor($section->course, $new)->id,
                    ]);

                    $section->meetings()->get()->each(function (SectionMeeting $meeting) use ($old, $new) {
                        if ($meeting->start_date->equalTo($old->start_date) && $meeting->end_date->equalTo($old->end_date)) {
                            $meeting->update(['start_date' => $new->start_date, 'end_date' => $new->end_date]);
                        }
                    });
                }

                $section->fill(Arr::only($data, ['section_code', 'capacity', 'org_unit_id', 'eligibility', 'exclusion', 'instruction_language', 'note']))->save();

                return $section;
            }, 'term_session_id');
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['section_code' => __('offering.errors.section_code_taken')]);
        }
    }
}
