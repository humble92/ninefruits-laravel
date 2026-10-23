<?php

namespace App\Domain\Grading\Actions;

use App\Domain\Grading\Data\TermClosePreflight;
use App\Domain\Grading\Enums\AcademicStanding;
use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Grading\Jobs\CloseTermRecordsJob;
use App\Domain\Grading\Jobs\FinishTermCloseJob;
use App\Domain\Grading\Services\AcademicStandingJudge;
use App\Domain\Grading\Services\TermRecordCalculator;
use App\Domain\Grading\Settings\GradingSettings;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Domain\Term\Actions\ChangeTermStatus;
use App\Domain\Term\Enums\TermStatus;
use App\Models\Grading\TermRecord;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\ValidationException;

/**
 * Term close (legacy: none; warnings and honors were reports over live views): every grade
 * of the term finalized, then per student the term record, academic standing and honors
 * eligibility (a batch of jobs), then honors ranks per program, the records closed as the
 * official snapshot and the term moved to grades closed.
 */
class CloseTerm
{
    private const int CHUNK = 100;

    public function __construct(
        private readonly TermRecordCalculator $records,
        private readonly AcademicStandingJudge $standing,
        private readonly GradingSettings $settings,
        private readonly ChangeTermStatus $status,
        private readonly AuditLogger $audit,
    ) {}

    public function preflight(Term $term): TermClosePreflight
    {
        $open = Section::query()->where('term_id', $term->id)
            ->whereHas('enrollments', fn ($query) => $query->where('status', EnrollmentStatus::Enrolled))
            ->with(['course', 'version'])
            ->withCount(['enrollments as unfinished_count' => fn ($query) => $query->where('status', EnrollmentStatus::Enrolled)])
            ->get()->sortBy(fn (Section $section) => $section->label())->values();
        $students = Enrollment::query()->where('term_id', $term->id)->where('grade_status', GradeStatus::Finalized)->distinct()->count('student_record_id');

        return new TermClosePreflight($term, $open, $students);
    }

    /**
     * Starts the close; it finishes in the queue. Null user: a console run.
     */
    public function close(Term $term, ?User $staff, ?string $reason = null): void
    {
        if ($staff !== null && ! $staff->can(Permission::GradesManage->value)) {
            throw new AuthorizationException;
        }

        if ($term->status !== TermStatus::Published) {
            throw ValidationException::withMessages(['term' => __('grading.errors.term_not_published')]);
        }

        $preflight = $this->preflight($term);

        if (! $preflight->isReady()) {
            throw ValidationException::withMessages(['term' => __('grading.errors.term_not_ready', ['count' => $preflight->openSections->count()])]);
        }

        $this->audit->record('term.close_started', $term, $reason, ['students' => $preflight->students], $staff);

        $jobs = Enrollment::query()->where('term_id', $term->id)->where('grade_status', GradeStatus::Finalized)
            ->distinct()->orderBy('student_record_id')->pluck('student_record_id')
            ->chunk(self::CHUNK)->map(fn ($ids) => new CloseTermRecordsJob($term->id, $ids->values()->all()))->values()->all();

        $finish = new FinishTermCloseJob($term->id, $staff?->id, $reason);

        $jobs === []
            ? dispatch($finish)
            : Bus::chain([Bus::batch($jobs)->name('term-close:'.$term->code), $finish])->dispatch();
    }

    /**
     * @param  list<int>  $studentRecordIds
     */
    public function closeRecords(Term $term, array $studentRecordIds): void
    {
        foreach (StudentRecord::query()->whereIn('id', $studentRecordIds)->get() as $record) {
            $termRecord = $this->records->recalculate($record, $term)->get($term->id);

            if ($termRecord === null) {
                continue;
            }

            $this->standing->judge($termRecord, $term);
            $termRecord->forceFill(['is_honors' => $this->honorsEligible($termRecord, $term), 'honors_rank' => null])->save();

            if ($termRecord->academic_standing !== null) {
                $record->forceFill(['academic_standing' => $termRecord->academic_standing->value])->save();
            }
        }
    }

    public function finish(Term $term, ?User $staff, ?string $reason): void
    {
        $this->rankHonors($term);
        TermRecord::query()->where('term_id', $term->id)->update(['closed_at' => CarbonImmutable::now()]);

        if ($term->status === TermStatus::Published) {
            $this->status->handle($term, TermStatus::GradesClosed, $reason, $staff);
        }

        $records = TermRecord::query()->where('term_id', $term->id);
        $this->audit->record('term.closed', $term, $reason, [
            'records' => (clone $records)->count(),
            'warnings' => (clone $records)->where('academic_standing', AcademicStanding::Warning)->count(),
            'dismissal_reviews' => (clone $records)->where('academic_standing', AcademicStanding::DismissalReview)->count(),
            'honors' => (clone $records)->where('is_honors', true)->count(),
        ], $staff);
    }

    private function honorsEligible(TermRecord $record, Term $term): bool
    {
        if ($this->settings->standing_regular_terms_only && ! $term->type->is_regular) {
            return false;
        }

        if ($record->term_gpa === null || (float) $record->term_gpa < $this->settings->honors_min_gpa
            || (float) $record->gpa_credits < $this->settings->honors_min_credits) {
            return false;
        }

        return ! $this->settings->honors_exclude_failing || ! Enrollment::query()->where('student_record_id', $record->student_record_id)
            ->where('term_id', $term->id)->where('grade_status', GradeStatus::Finalized)->where('is_passed', false)->exists();
    }

    /**
     * Ranks honors students per program by term GPA (1, 1, 3 on ties).
     */
    private function rankHonors(Term $term): void
    {
        $records = TermRecord::query()->where('term_id', $term->id)->where('is_honors', true)->with('studentRecord')->get()
            ->groupBy(fn (TermRecord $record) => $record->studentRecord->program_id);

        foreach ($records as $program) {
            $sorted = $program->sortByDesc(fn (TermRecord $record) => (float) $record->term_gpa)->values();
            $rank = 0;
            $previous = null;

            foreach ($sorted as $index => $record) {
                if ($record->term_gpa !== $previous) {
                    $rank = $index + 1;
                    $previous = $record->term_gpa;
                }

                $record->forceFill(['honors_rank' => $rank])->save();
            }
        }
    }
}
