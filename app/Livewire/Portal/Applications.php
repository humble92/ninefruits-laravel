<?php

namespace App\Livewire\Portal;

use App\Domain\Admission\Actions\StartApplication;
use App\Models\Admission\AdmissionCycle;
use App\Models\Admission\Application;
use App\Models\Organization\Program;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The applicant's applications and the cycles open for a new one. The same page serves
 * students and graduates applying again.
 */
#[Layout('components.layouts.portal')]
class Applications extends Component
{
    public ?int $cycleId = null;

    public ?int $programId = null;

    public function mount(): void
    {
        abort_if($this->user()->person_id === null, 404);
    }

    public function updatedCycleId(): void
    {
        $this->programId = null;
    }

    public function start(StartApplication $start): mixed
    {
        $this->validate(['cycleId' => ['required', 'integer'], 'programId' => ['required', 'integer']]);

        $application = $start->handle(
            $this->user(),
            AdmissionCycle::query()->findOrFail($this->cycleId),
            Program::query()->findOrFail($this->programId),
        );

        return $this->redirectRoute('portal.applications.show', $application, navigate: false);
    }

    public function render(): View
    {
        $cycles = AdmissionCycle::query()->acceptingApplications()->with('programs.program')->orderBy('opens_at')->get();

        return view('livewire.portal.applications', [
            'applications' => Application::query()->where('person_id', $this->user()->person_id)
                ->with(['cycle', 'program'])->latest()->get(),
            'cycles' => $cycles,
            'programs' => $cycles->firstWhere('id', $this->cycleId)->programs ?? collect(),
        ])->title(__('admissions.portal.title'));
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
