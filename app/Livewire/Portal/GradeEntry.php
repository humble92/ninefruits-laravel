<?php

namespace App\Livewire\Portal;

use App\Domain\Curriculum\Enums\GradingBasis;
use App\Domain\Grading\Actions\ManageEvaluationMethodRequests;
use App\Domain\Grading\Actions\ManageGradeChanges;
use App\Domain\Grading\Actions\SaveGrades;
use App\Domain\Grading\Enums\EvaluationMethod;
use App\Domain\Grading\Enums\GradeChangeStatus;
use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Grading\Services\GradeDistributionCheck;
use App\Domain\Grading\Services\GradeOptions;
use App\Domain\Lms\Actions\PullGrades;
use App\Domain\Lms\Enums\LinkStatus;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Term\Enums\TermWindowType;
use App\Domain\Term\Services\TermResolver;
use App\Models\Grading\EvaluationMethodRequest;
use App\Models\Grading\GradeChangeRequest;
use App\Models\Grading\GradeScaleItem;
use App\Models\Lms\LmsPlatform;
use App\Models\Offering\Section;
use App\Models\Registration\Enrollment;
use App\Models\User;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Grade entry for a section (legacy prof/opobj*). Absolute evaluation: the instructors
 * enter scores and the grades follow. Relative evaluation: grades only, no scores, and
 * the grade caps are checked before submission. Drafts and submission while the grade
 * entry window is open; after finalization a grade change request with a reason.
 */
#[Layout('components.layouts.portal')]
class GradeEntry extends Component
{
    public Section $section;

    /** @var array<int, array{grade_code: string|null, score: string|null}> by enrollment id */
    public array $grades = [];

    public ?int $changeFor = null;

    public string $changeGrade = '';

    public string $changeScore = '';

    public string $changeReason = '';

    /** Staff: why relative grades outside the caps are submitted anyway. */
    public string $exceptionReason = '';

    public string $methodReason = '';

    /** @var list<string> */
    public array $messages = [];

    public string $messageTone = 'info';

    public function mount(Section $section): void
    {
        // Viewing; entering, submitting and requesting changes are authorized by the actions.
        abort_unless($section->isTaughtBy($this->user()) || collect([Permission::GradesManage, Permission::GradesView, Permission::GradeChangesRequest])
            ->contains(fn (Permission $permission) => $section->isInScopeOf($this->user(), $permission)), 403);

        $this->section = $section->load(['course', 'version', 'session', 'term']);
        $this->fillGrades();
    }

    public function hydrate(): void
    {
        $this->section->loadMissing(['course', 'version', 'session', 'term']);
    }

    /**
     * Absolute evaluation: a score shows the grade it gives right away.
     *
     * @var array<int, string|null> by enrollment id
     */
    public array $previews = [];

    public function updatedGrades(mixed $value, string $key): void
    {
        [$id, $field] = explode('.', $key) + [1 => null];

        if ($field !== 'score') {
            return;
        }

        $enrollment = $this->enrollments()->firstWhere('id', (int) $id);

        try {
            $choice = $enrollment === null || blank($value) ? null : app(GradeOptions::class)->resolve($enrollment, null, (string) $value);
            $this->previews[(int) $id] = $choice?->item?->code;
            $this->grades[(int) $id]['grade_code'] = null;
        } catch (ValidationException) {
            $this->previews[(int) $id] = null;
        }
    }

    public function save(): void
    {
        $this->run(fn () => $this->say([__('grading.entry.saved', ['count' => app(SaveGrades::class)->save($this->section, $this->editableGrades(), $this->user())])], 'success'));
    }

    /**
     * Relative evaluation: saves the drafts and shows how the grades sit against the caps.
     */
    public function validateDistribution(): void
    {
        $this->run(function () {
            app(SaveGrades::class)->save($this->section, $this->editableGrades(), $this->user());
            $distribution = app(GradeDistributionCheck::class)->forSection($this->section->refresh());
            $this->say($distribution->passes() ? [__('grading.entry.distribution_ok')] : $distribution->violations, $distribution->passes() ? 'success' : 'error');
        });
    }

    public function submit(): void
    {
        $this->run(function () {
            app(SaveGrades::class)->save($this->section, $this->editableGrades(), $this->user());
            $count = app(SaveGrades::class)->submit($this->section, $this->user(), $this->exceptionReason ?: null);
            $this->exceptionReason = '';
            $this->say([__('grading.entry.submitted', ['count' => $count])], 'success');
        });
    }

    public function requestMethod(): void
    {
        $this->validate(['methodReason' => 'required|string|max:2000']);
        $other = $this->section->evaluation_method === EvaluationMethod::Absolute ? EvaluationMethod::Relative : EvaluationMethod::Absolute;

        $this->run(function () use ($other) {
            app(ManageEvaluationMethodRequests::class)->request($this->section, $other, $this->methodReason, $this->user());
            $this->methodReason = '';
            $this->say([__('grading.entry.method_requested', ['method' => $other->label()])], 'success');
        });
    }

    public function cancelMethodRequest(int $requestId): void
    {
        $request = EvaluationMethodRequest::query()->where('section_id', $this->section->id)->findOrFail($requestId);
        $this->run(fn () => app(ManageEvaluationMethodRequests::class)->cancel($request, $this->user()));
    }

    public function startChange(int $enrollmentId): void
    {
        $this->changeFor = $enrollmentId;
        $this->changeGrade = '';
        $this->changeScore = '';
        $this->changeReason = '';
    }

    /**
     * Absolute evaluation asks for the new score, relative for the new grade.
     */
    public function requestChange(): void
    {
        $this->validate([
            'changeGrade' => 'nullable|required_without:changeScore|string|max:8',
            'changeScore' => 'nullable|required_without:changeGrade|numeric|between:0,100',
            'changeReason' => 'required|string|max:2000',
        ]);
        $enrollment = Enrollment::query()->where('section_id', $this->section->id)->with(['section', 'version'])->findOrFail($this->changeFor);

        $this->run(function () use ($enrollment) {
            app(ManageGradeChanges::class)->request($enrollment, $this->changeGrade ?: null, $this->changeReason, $this->user(), $this->changeScore === '' ? null : $this->changeScore);
            $this->changeFor = null;
            $this->say([__('grading.entry.change_requested')], 'success');
        });
    }

    public function cancelChange(int $requestId): void
    {
        $request = GradeChangeRequest::query()->whereHas('enrollment', fn ($query) => $query->where('section_id', $this->section->id))->findOrFail($requestId);
        $this->run(fn () => app(ManageGradeChanges::class)->cancel($request, $this->user()));
    }

    public function render(GradeOptions $options, TermResolver $terms): View
    {
        $enrollments = $this->enrollments();
        $counts = $enrollments->countBy(fn (Enrollment $enrollment) => $enrollment->grade_status->value)->all();
        $canEnter = app(SaveGrades::class)->canEnter($this->section, $this->user());
        $byStaff = $this->section->isInScopeOf($this->user(), Permission::GradesManage);

        return view('livewire.portal.grade-entry', [
            'enrollments' => $enrollments,
            'options' => fn (Enrollment $enrollment) => $options->for($enrollment)->map(fn (GradeScaleItem $item) => $item->code),
            'takesScore' => fn (Enrollment $enrollment) => $options->takesScore($enrollment),
            'absolute' => $this->section->evaluation_method === EvaluationMethod::Absolute,
            'scale' => $options->scale(),
            'relative' => $relative = $this->section->evaluation_method === EvaluationMethod::Relative && $this->section->version->grading_basis === GradingBasis::Graded,
            'distribution' => $relative ? app(GradeDistributionCheck::class)->forSection($this->section) : null,
            'methodRequest' => EvaluationMethodRequest::query()->where('section_id', $this->section->id)->pending()->first(),
            'canRequestMethod' => $this->section->isTaughtBy($this->user()) && ($counts['submitted'] ?? 0) + ($counts['finalized'] ?? 0) === 0,
            'otherMethod' => $this->section->evaluation_method === EvaluationMethod::Absolute ? EvaluationMethod::Relative : EvaluationMethod::Absolute,
            'exceptionBy' => $this->section->grade_exception_at === null ? null : User::query()->find($this->section->grade_exception_by_id)?->email,
            'editable' => fn (Enrollment $enrollment) => $canEnter && $enrollment->status === EnrollmentStatus::Enrolled
                && in_array($enrollment->grade_status, $byStaff ? [GradeStatus::None, GradeStatus::Draft, GradeStatus::Submitted] : [GradeStatus::None, GradeStatus::Draft], true),
            'canEnter' => $canEnter,
            'windowOpen' => $terms->isOpenInSession(TermWindowType::GradeEntry, $this->section->session),
            'pendingStatus' => GradeChangeStatus::Pending,
            'counts' => $counts,
        ])->title(__('grading.entry.title').' '.$this->section->label());
    }

    /**
     * @return Collection<int, Enrollment>
     */
    private function enrollments(): Collection
    {
        return Enrollment::query()->where('section_id', $this->section->id)
            ->whereIn('status', [EnrollmentStatus::Enrolled, EnrollmentStatus::Completed, EnrollmentStatus::Withdrawn])
            ->with(['studentRecord.person', 'studentRecord.program', 'version', 'section', 'gradeChangeRequests'])
            ->get()->sortBy(fn (Enrollment $enrollment) => $enrollment->studentRecord->student_number)->values();
    }

    /**
     * Loads the platform's final scores into the score fields as a draft; nothing is saved
     * until the instructor does (porting/porting-plan.md section 12).
     */
    public function importLmsScores(PullGrades $pull): void
    {
        $this->messages = [];
        $imported = 0;

        foreach (LmsPlatform::query()->where('is_active', true)->where('should_pull_grades', true)->get() as $platform) {
            foreach ($pull->handle($platform, $this->section) as $enrollmentId => $score) {
                if (array_key_exists($enrollmentId, $this->grades)) {
                    $this->grades[$enrollmentId]['score'] = (string) $score;
                    $imported++;
                }
            }
        }

        [$this->messages, $this->messageTone] = [[__('grading.entry.lms_imported', ['count' => $imported])], $imported > 0 ? 'success' : 'info'];
    }

    public function hasLmsScores(): bool
    {
        return LmsPlatform::query()->where('is_active', true)->where('should_pull_grades', true)
            ->whereHas('courseLinks', fn ($q) => $q->where('section_id', $this->section->id)->where('status', LinkStatus::Linked))->exists();
    }

    private function fillGrades(): void
    {
        $this->previews = [];

        $this->grades = $this->enrollments()->mapWithKeys(fn (Enrollment $enrollment) => [$enrollment->id => [
            'grade_code' => $enrollment->grade_code,
            'score' => $enrollment->score === null ? null : rtrim(rtrim($enrollment->score, '0'), '.'),
        ]])->all();
    }

    /**
     * @return array<int, array{grade_code: string|null, score: string|null}>
     */
    private function editableGrades(): array
    {
        $statuses = $this->section->isInScopeOf($this->user(), Permission::GradesManage)
            ? [GradeStatus::None, GradeStatus::Draft, GradeStatus::Submitted]
            : [GradeStatus::None, GradeStatus::Draft];
        $ids = Enrollment::query()->where('section_id', $this->section->id)->where('status', EnrollmentStatus::Enrolled)
            ->whereIn('grade_status', $statuses)->pluck('id')->all();

        return array_intersect_key($this->grades, array_flip($ids));
    }

    private function run(Closure $callback): void
    {
        try {
            $callback();
            $this->fillGrades();
        } catch (ValidationException $exception) {
            $this->say(collect($exception->errors())->flatten()->all(), 'error');
        }
    }

    /**
     * @param  list<string>  $messages
     */
    private function say(array $messages, string $tone): void
    {
        $this->messages = $messages;
        $this->messageTone = $tone;
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
