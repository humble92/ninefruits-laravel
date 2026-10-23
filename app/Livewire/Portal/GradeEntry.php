<?php

namespace App\Livewire\Portal;

use App\Domain\Grading\Actions\ManageGradeChanges;
use App\Domain\Grading\Actions\SaveGrades;
use App\Domain\Grading\Enums\EvaluationMethod;
use App\Domain\Grading\Enums\GradeChangeStatus;
use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Grading\Services\GradeOptions;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Term\Enums\TermWindowType;
use App\Domain\Term\Services\TermResolver;
use App\Models\Grading\GradeChangeRequest;
use App\Models\Grading\GradeScaleItem;
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
 * enter scores and the grades follow (on the institution's scale and the comparison
 * scale). Relative evaluation: grades only, no scores. Drafts and submission while the
 * grade entry window is open; after finalization a grade change request with a reason.
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

    /**
     * Absolute evaluation: a score shows the grade it gives (on both scales) right away.
     *
     * @var array<int, array{grade: string|null, comparison: string|null}>
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
            $this->previews[(int) $id] = ['grade' => $choice?->item?->code, 'comparison' => $choice?->comparison?->code];
            $this->grades[(int) $id]['grade_code'] = null;
        } catch (ValidationException) {
            $this->previews[(int) $id] = ['grade' => null, 'comparison' => null];
        }
    }

    public function save(): void
    {
        $this->run(fn () => $this->say([__('grading.entry.saved', ['count' => app(SaveGrades::class)->save($this->section, $this->editableGrades(), $this->user())])], 'success'));
    }

    public function submit(): void
    {
        $this->run(function () {
            app(SaveGrades::class)->save($this->section, $this->editableGrades(), $this->user());
            $this->say([__('grading.entry.submitted', ['count' => app(SaveGrades::class)->submit($this->section, $this->user())])], 'success');
        });
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
        $canEnter = app(SaveGrades::class)->canEnter($this->section, $this->user());
        $byStaff = $this->section->isInScopeOf($this->user(), Permission::GradesManage);

        return view('livewire.portal.grade-entry', [
            'enrollments' => $enrollments,
            'options' => fn (Enrollment $enrollment) => $options->for($enrollment)->map(fn (GradeScaleItem $item) => $item->code),
            'takesScore' => fn (Enrollment $enrollment) => $options->takesScore($enrollment),
            'absolute' => $this->section->evaluation_method === EvaluationMethod::Absolute,
            'scale' => $options->scale(),
            'comparisonScale' => $options->comparisonScale(),
            'editable' => fn (Enrollment $enrollment) => $canEnter && $enrollment->status === EnrollmentStatus::Enrolled
                && in_array($enrollment->grade_status, $byStaff ? [GradeStatus::None, GradeStatus::Draft, GradeStatus::Submitted] : [GradeStatus::None, GradeStatus::Draft], true),
            'canEnter' => $canEnter,
            'windowOpen' => $terms->isOpenInSession(TermWindowType::GradeEntry, $this->section->session),
            'pendingStatus' => GradeChangeStatus::Pending,
            'counts' => $enrollments->countBy(fn (Enrollment $enrollment) => $enrollment->grade_status->value),
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
