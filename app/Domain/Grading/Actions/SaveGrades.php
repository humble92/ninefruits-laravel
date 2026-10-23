<?php

namespace App\Domain\Grading\Actions;

use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Grading\Services\GradeOptions;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Domain\Term\Enums\TermWindowType;
use App\Domain\Term\Services\TermResolver;
use App\Models\Offering\Section;
use App\Models\Registration\Enrollment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Grade entry for a section (legacy prof/opobj_editgo.php, which overwrote saved grades on
 * any replayed request). Instructors of the section save drafts and submit while the
 * section's grade entry window is open; staff with grades.manage for the section may do so
 * at any time until the grades are finalized. Submitted grades are locked for instructors.
 */
class SaveGrades
{
    public function __construct(
        private readonly GradeOptions $options,
        private readonly TermResolver $terms,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Absolute evaluation takes scores (the grades follow), relative evaluation grades only
     * (GradeOptions).
     *
     * @param  array<int, array{grade_code?: string|null, score?: string|int|float|null}>  $grades  by enrollment id
     * @return int saved grades
     */
    public function save(Section $section, array $grades, User $actor): int
    {
        $byStaff = $this->authorize($section, $actor);
        $now = CarbonImmutable::now();
        $saved = 0;

        DB::transaction(function () use ($section, $grades, $actor, $byStaff, $now, &$saved) {
            $enrollments = $this->editable($section, $byStaff)->whereIn('id', array_keys($grades))->get()->keyBy('id');

            foreach ($grades as $enrollmentId => $grade) {
                $enrollment = $enrollments->get($enrollmentId)
                    ?? throw ValidationException::withMessages(['grades' => __('grading.errors.not_editable')]);
                $score = isset($grade['score']) && $grade['score'] !== '' ? (string) $grade['score'] : null;
                $choice = $this->options->resolve($enrollment, $grade['grade_code'] ?? null, $score);

                $enrollment->forceFill([
                    ...$choice->attributes(),
                    // Staff corrections before finalization keep a submitted grade submitted.
                    'grade_status' => match (true) {
                        $choice->isEmpty() => GradeStatus::None,
                        $choice->item !== null && $enrollment->grade_status === GradeStatus::Submitted => GradeStatus::Submitted,
                        default => GradeStatus::Draft,
                    },
                ]);

                if ($enrollment->isDirty()) {
                    $enrollment->forceFill(['grade_entered_at' => $now, 'grade_entered_by_id' => $actor->id])->save();
                    $saved++;
                }
            }
        });

        return $saved;
    }

    /**
     * Submits every grade of the section; all registered students need a grade.
     */
    public function submit(Section $section, User $actor): int
    {
        $byStaff = $this->authorize($section, $actor);

        return DB::transaction(function () use ($section, $actor, $byStaff) {
            $enrollments = $this->editable($section, $byStaff)->where('grade_status', '!=', GradeStatus::Submitted)->get();
            $missing = $enrollments->filter(fn (Enrollment $enrollment) => $enrollment->grade_code === null)->count();

            if ($missing > 0) {
                throw ValidationException::withMessages(['grades' => __('grading.errors.missing_grades', ['count' => $missing])]);
            }

            Enrollment::query()->whereIn('id', $enrollments->modelKeys())->update([
                'grade_status' => GradeStatus::Submitted, 'grade_submitted_at' => CarbonImmutable::now(), 'grade_submitted_by_id' => $actor->id,
            ]);
            $this->audit->record('grades.submitted', $section, null, ['count' => $enrollments->count()], $actor);

            return $enrollments->count();
        });
    }

    /**
     * Whether the user may enter the section's grades now (for screens).
     */
    public function canEnter(Section $section, User $user): bool
    {
        try {
            $this->authorize($section, $user);

            return true;
        } catch (AuthorizationException|ValidationException) {
            return false;
        }
    }

    /**
     * @return bool whether the actor acts as staff
     */
    private function authorize(Section $section, User $actor): bool
    {
        if ($section->isInScopeOf($actor, Permission::GradesManage)) {
            return true;
        }

        if (! $section->isTaughtBy($actor)) {
            throw new AuthorizationException;
        }

        if (! $this->terms->isOpenInSession(TermWindowType::GradeEntry, $section->session)) {
            throw ValidationException::withMessages(['grades' => __('grading.errors.entry_closed')]);
        }

        return false;
    }

    /**
     * @return Builder<Enrollment>
     */
    private function editable(Section $section, bool $byStaff): Builder
    {
        return Enrollment::query()->where('section_id', $section->id)->where('status', EnrollmentStatus::Enrolled)
            ->whereIn('grade_status', $byStaff ? [GradeStatus::None, GradeStatus::Draft, GradeStatus::Submitted] : [GradeStatus::None, GradeStatus::Draft])
            ->with(['version', 'section']);
    }
}
