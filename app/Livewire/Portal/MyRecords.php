<?php

namespace App\Livewire\Portal;

use App\Domain\Person\Actions\CancelAdditionalProgramRequest;
use App\Domain\Person\Actions\CancelStatusChange;
use App\Domain\Person\Actions\RequestAdditionalProgram;
use App\Domain\Person\Actions\RequestStatusChange;
use App\Domain\Person\Enums\AdditionalProgramRole;
use App\Domain\Person\Enums\StatusChangeStatus;
use App\Domain\Person\Services\CurrentStudentRecord;
use App\Domain\Term\Enums\TermWindowType;
use App\Domain\Term\Services\TermResolver;
use App\Models\Organization\Program;
use App\Models\Person\AdditionalProgramRequest;
use App\Models\Person\StudentRecord;
use App\Models\Person\StudentStatusChange;
use App\Models\Person\StudentStatusChangeType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Student and alumni view of their own records. Requests are possible only on current
 * records; graduates see their records read only.
 */
#[Layout('components.layouts.portal')]
class MyRecords extends Component
{
    public ?int $statusChangeTypeId = null;

    public string $effectiveDate = '';

    public string $reason = '';

    public ?int $additionalProgramId = null;

    public string $additionalRole = 'second_major';

    public string $additionalStatement = '';

    public function mount(): void
    {
        abort_if($this->user()->person_id === null, 404);

        $this->effectiveDate = CarbonImmutable::today()->toDateString();
    }

    public function choose(int $recordId, CurrentStudentRecord $current): void
    {
        $current->choose($this->user(), $recordId);
        $this->reset('statusChangeTypeId', 'reason');
    }

    public function requestChange(CurrentStudentRecord $current, RequestStatusChange $request): void
    {
        $this->validate([
            'statusChangeTypeId' => ['required', 'integer'],
            'effectiveDate' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $record = $current->get($this->user()) ?? abort(404);
        $type = StudentStatusChangeType::query()->findOrFail($this->statusChangeTypeId);

        $request->handle($record, $type, CarbonImmutable::parse($this->effectiveDate), $this->reason, $this->user());

        $this->reset('statusChangeTypeId', 'reason');
        session()->flash('status', __('portal.records.request_submitted'));
    }

    public function cancel(int $changeId, CancelStatusChange $cancel): void
    {
        $change = StudentStatusChange::query()->where('requested_by_id', $this->user()->id)->findOrFail($changeId);

        $cancel->handle($change, $this->user());
    }

    public function applyAdditionalProgram(CurrentStudentRecord $current, RequestAdditionalProgram $request): void
    {
        $this->validate([
            'additionalProgramId' => ['required', 'integer'],
            'additionalRole' => ['required', Rule::enum(AdditionalProgramRole::class)],
            'additionalStatement' => ['nullable', 'string', 'max:2000'],
        ]);

        $record = $current->get($this->user()) ?? abort(404);
        $request->handle($record, Program::query()->findOrFail($this->additionalProgramId), AdditionalProgramRole::from($this->additionalRole), $this->additionalStatement ?: null, $this->user());

        $this->reset('additionalProgramId', 'additionalStatement');
        session()->flash('status', __('portal.records.additional_program_requested'));
    }

    public function cancelAdditionalProgram(int $requestId, CancelAdditionalProgramRequest $cancel): void
    {
        $cancel->handle(AdditionalProgramRequest::query()->findOrFail($requestId), $this->user());
    }

    public function render(CurrentStudentRecord $current): View
    {
        $record = $current->get($this->user());
        $record?->load(['program', 'orgUnit', 'advisorAssignments.instructor.person', 'statusChanges.type', 'additionalPrograms.program']);
        $applicationTerm = $record !== null && $record->status->isActive()
            ? app(TermResolver::class)->termsOpenFor(TermWindowType::AdditionalProgramApplication, $record)->first()
            : null;

        return view('livewire.portal.my-records', [
            'records' => $current->all($this->user()),
            'record' => $record,
            'requestableTypes' => $record !== null && $record->status->isActive() ? $this->requestableTypes($record) : collect(),
            'pending' => $record?->statusChanges->firstWhere('status', StatusChangeStatus::Requested),
            'additionalRequests' => $record === null ? collect() : AdditionalProgramRequest::query()->where('student_record_id', $record->id)->with(['program', 'term'])->latest('requested_at')->get(),
            'applicationTerm' => $applicationTerm,
            'additionalOptions' => $applicationTerm === null ? collect() : Program::query()->effectiveOn()
                ->where($this->additionalRole === AdditionalProgramRole::Minor->value ? 'allows_minor' : 'allows_second_major', true)
                ->whereKeyNot($record->program_id)->get()->sortBy('name'),
        ])->title(__('portal.records.title'));
    }

    /**
     * @return Collection<int, StudentStatusChangeType>
     */
    private function requestableTypes(StudentRecord $record): Collection
    {
        return StudentStatusChangeType::query()->where('can_student_request', true)->where('is_active', true)->orderBy('sort_order')->get()
            ->filter(fn (StudentStatusChangeType $type) => $type->appliesFrom($record->status))
            ->values();
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
