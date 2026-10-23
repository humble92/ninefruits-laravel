<?php

namespace App\Domain\Grading\Actions;

use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Grading\Events\GradesFinalized;
use App\Domain\Grading\Notifications\GradesReturnedNotification;
use App\Domain\Grading\Services\TermRecordCalculator;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Staff with grades.manage for the section finalize its submitted grades (the enrollments
 * become completed) or return them to the instructors with a reason. The legacy had no such
 * step: a saved grade was final for the professor and 교무처 edited it directly.
 */
class FinalizeGrades
{
    public function __construct(
        private readonly TermRecordCalculator $records,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return int finalized grades
     */
    public function finalize(Section $section, User $staff): int
    {
        $this->authorize($section, $staff);

        $finalized = DB::transaction(function () use ($section, $staff) {
            $open = Enrollment::query()->where('section_id', $section->id)->where('status', EnrollmentStatus::Enrolled)->lockForUpdate()->get();
            $notSubmitted = $open->where('grade_status', '!=', GradeStatus::Submitted)->count();

            if ($open->isEmpty() || $notSubmitted > 0) {
                throw ValidationException::withMessages(['grades' => __('grading.errors.not_all_submitted', ['count' => $notSubmitted])]);
            }

            Enrollment::query()->whereIn('id', $open->modelKeys())->update([
                'status' => EnrollmentStatus::Completed,
                'grade_status' => GradeStatus::Finalized,
                'grade_finalized_at' => CarbonImmutable::now(),
                'grade_finalized_by_id' => $staff->id,
            ]);
            $this->audit->record('grades.finalized', $section, null, ['count' => $open->count()], $staff);

            return $open;
        });

        foreach (StudentRecord::query()->whereIn('id', $finalized->pluck('student_record_id'))->get() as $record) {
            $this->records->recalculate($record, $section->term);
        }

        GradesFinalized::dispatch($section, $finalized->modelKeys());

        return $finalized->count();
    }

    /**
     * Back to the instructors as drafts, e.g. when a grade looks wrong before finalizing.
     */
    public function return(Section $section, User $staff, string $reason): int
    {
        $this->authorize($section, $staff);

        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => __('registration.errors.reason_required')]);
        }

        $count = Enrollment::query()->where('section_id', $section->id)->where('status', EnrollmentStatus::Enrolled)
            ->where('grade_status', GradeStatus::Submitted)
            ->update(['grade_status' => GradeStatus::Draft, 'grade_submitted_at' => null, 'grade_submitted_by_id' => null]);

        if ($count === 0) {
            throw ValidationException::withMessages(['grades' => __('grading.errors.nothing_submitted')]);
        }

        $this->audit->record('grades.returned', $section, $reason, ['count' => $count], $staff);

        foreach ($section->instructors()->with('person.user')->get() as $instructor) {
            $instructor->person->user?->notify(new GradesReturnedNotification($section, $reason));
        }

        return $count;
    }

    private function authorize(Section $section, User $staff): void
    {
        if (! $section->isInScopeOf($staff, Permission::GradesManage)) {
            throw new AuthorizationException;
        }
    }
}
