<?php

namespace App\Domain\Offering\Actions;

use App\Domain\Offering\Data\SectionCopyReport;
use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Offering\Services\ScheduleConflicts;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Offering\Section;
use App\Models\Offering\SectionInstructor;
use App\Models\Offering\SectionMeeting;
use App\Models\Offering\SectionSeatGroup;
use App\Models\Offering\Syllabus;
use App\Models\Term\Term;
use App\Models\Term\TermSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Starts a term's offering from an earlier term (e.g. last fall into this fall): sections
 * that were not cancelled, in the user's scope, as drafts with the course version of the
 * new session, current instructors, meetings with their rooms, seat groups (release times
 * shifted) and the syllabus as an unpublished draft.
 *
 * Sessions are matched by code (full term otherwise). A room or instructor that is now
 * taken is left out and reported; the section is still copied. A dry run does the whole
 * copy in a transaction and rolls it back, so its report is exact.
 */
class CopySections
{
    public function __construct(
        private readonly ScheduleConflicts $conflicts,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  list<int>|null  $orgUnitIds  Only sections offered by these units
     */
    public function handle(Term $from, Term $to, User $user, ?array $orgUnitIds = null, bool $dryRun = false): SectionCopyReport
    {
        if ($from->is($to)) {
            throw ValidationException::withMessages(['to_term_id' => __('offering.errors.copy_same_term')]);
        }

        $report = new SectionCopyReport;
        $shiftDays = (int) $from->start_date->diffInDays($to->start_date, false);
        $targetSessions = $to->sessions()->get()->keyBy('code');
        $fullTerm = $to->fullTermSession();

        $sections = Section::query()
            ->where('term_id', $from->id)
            ->where('status', '!=', SectionStatus::Cancelled)
            ->when($orgUnitIds !== null, fn (Builder $query) => $query->whereIn('org_unit_id', $orgUnitIds))
            ->tap(fn (Builder $query) => Section::constrainManageableBy($query, $user))
            ->with(['course', 'session', 'instructorAssignments.instructor', 'meetings', 'seatGroups', 'syllabus'])
            ->orderBy('course_id')->orderBy('section_code')
            ->get()
            ->filter(fn (Section $section) => $section->isManageableBy($user));

        DB::beginTransaction();

        try {
            foreach ($sections as $section) {
                $this->copy($section, $to, $targetSessions->get($section->session->code, $fullTerm), $shiftDays, $report);
            }

            if (! $dryRun) {
                $this->audit->record('sections.copied', $to, null, ['from_term' => $from->code, ...$report->summary()], $user);
            }
        } catch (\Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        $dryRun ? DB::rollBack() : DB::commit();

        return $report;
    }

    private function copy(Section $source, Term $to, TermSession $session, int $shiftDays, SectionCopyReport $report): void
    {
        $label = $source->label();

        if (Section::query()->where('term_id', $to->id)->where('course_id', $source->course_id)->where('section_code', $source->section_code)->exists()) {
            $report->skip($label, __('offering.copy.exists'));

            return;
        }

        $version = $source->course->versionOn($session->start_date);

        if ($version === null) {
            $report->skip($label, __('offering.copy.no_version'));

            return;
        }

        $section = new Section([
            ...$source->only(['section_code', 'capacity', 'waitlist_capacity', 'org_unit_id', 'eligibility', 'exclusion', 'instruction_language']),
            'note' => $source->getTranslations('note'),
            'term_id' => $to->id,
            'term_session_id' => $session->id,
            'course_id' => $source->course_id,
            'course_version_id' => $version->id,
        ]);
        $section->forceFill(['copied_from_section_id' => $source->id])->save();
        $report->created($label);

        foreach ($source->instructorAssignments as $assignment) {
            if (! $assignment->instructor->isCurrent($session->start_date)) {
                $report->note($label, __('offering.copy.instructor_left', ['instructor' => $assignment->instructor->employee_number]));

                continue;
            }

            SectionInstructor::query()->create(['section_id' => $section->id, 'instructor_id' => $assignment->instructor_id, 'role' => $assignment->role]);
        }

        foreach ($source->meetings as $meeting) {
            $this->copyMeeting($meeting, $section, $session, $label, $report);
        }

        foreach ($source->seatGroups as $group) {
            SectionSeatGroup::query()->create([
                'section_id' => $section->id,
                'name' => $group->getTranslations('name'),
                'capacity' => $group->capacity,
                'eligibility' => $group->eligibility,
                'releases_at' => $group->releases_at?->addDays($shiftDays),
                'sort_order' => $group->sort_order,
            ]);
        }

        if ($source->syllabus !== null) {
            Syllabus::query()->create(['section_id' => $section->id, 'language' => $source->syllabus->language, 'content' => $source->syllabus->content]);
        }
    }

    private function copyMeeting(SectionMeeting $meeting, Section $section, TermSession $session, string $label, SectionCopyReport $report): void
    {
        $attributes = [
            'section_id' => $section->id,
            'weekday' => $meeting->weekday,
            'start_time' => $meeting->start_time,
            'end_time' => $meeting->end_time,
            'room_id' => $meeting->room_id,
            'start_date' => $session->start_date,
            'end_date' => $session->end_date,
        ];

        foreach ([$attributes, [...$attributes, 'room_id' => null]] as $attempt => $values) {
            try {
                $this->conflicts->guard(fn () => SectionMeeting::query()->create($values));

                if ($attempt === 1) {
                    $report->note($label, __('offering.copy.room_taken', ['time' => $meeting->timeLabel()]));
                }

                return;
            } catch (ValidationException $exception) {
                if ($attempt === 1 || $values['room_id'] === null) {
                    // Without a room it can only be an instructor conflict: drop the meeting.
                    $report->note($label, collect($exception->errors())->flatten()->first());

                    return;
                }
            }
        }
    }
}
