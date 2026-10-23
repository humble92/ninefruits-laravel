<?php

namespace App\Domain\Evaluation\Services;

use App\Domain\Evaluation\Settings\EvaluationSettings;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Term\Enums\TermWindowType;
use App\Domain\Term\Services\TermResolver;
use App\Models\Evaluation\EvaluationCompletion;
use App\Models\Evaluation\TermEvaluation;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * What a student has to evaluate (legacy counsel/student_list.php): registered or completed
 * courses of terms with a course evaluation, whose course requires one, while the course
 * evaluation window of the section's session is open for the student. Withdrawn and
 * dropped courses are not evaluated.
 */
class EvaluationTasks
{
    public function __construct(
        private readonly TermResolver $terms,
        private readonly EvaluationSettings $settings,
    ) {}

    /**
     * Enrollments the student can evaluate now, done or not.
     *
     * @return Collection<int, Enrollment>
     */
    public function open(StudentRecord $record, ?CarbonImmutable $at = null): Collection
    {
        $termIds = TermEvaluation::query()->pluck('term_id');

        return Enrollment::query()->where('student_record_id', $record->id)
            ->whereIn('status', [EnrollmentStatus::Enrolled, EnrollmentStatus::Completed])
            ->whereIn('term_id', $termIds)
            ->whereHas('version', fn ($version) => $version->where('requires_evaluation', true))
            ->with(['section.session', 'section.course', 'version', 'term'])
            ->get()
            ->filter(fn (Enrollment $enrollment) => $this->terms->isOpenInSession(TermWindowType::CourseEvaluation, $enrollment->section->session, $record, $at))
            ->sortBy(fn (Enrollment $enrollment) => $enrollment->section->label())
            ->values();
    }

    /**
     * @return Collection<int, Enrollment>
     */
    public function pending(StudentRecord $record, ?CarbonImmutable $at = null): Collection
    {
        $open = $this->open($record, $at);
        $done = EvaluationCompletion::query()->whereIn('enrollment_id', $open->pluck('id'))->pluck('enrollment_id')->all();

        return $open->reject(fn (Enrollment $enrollment) => in_array($enrollment->id, $done, true))->values();
    }

    public function isOpen(Enrollment $enrollment, ?CarbonImmutable $at = null): bool
    {
        return $this->open($enrollment->loadMissing('studentRecord')->studentRecord, $at)->contains('id', $enrollment->id);
    }

    /**
     * Terms whose grades stay hidden: the evaluation is open and not finished (setting). The
     * block ends with the window, so a missed evaluation does not hide grades for good (the
     * legacy lock never ended).
     *
     * @return list<int>
     */
    public function blockedTermIds(StudentRecord $record, ?CarbonImmutable $at = null): array
    {
        if (! $this->settings->grades_require_evaluation) {
            return [];
        }

        return $this->pending($record, $at)->pluck('term_id')->unique()->values()->all();
    }
}
