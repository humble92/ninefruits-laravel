<?php

namespace App\Livewire\Portal;

use App\Models\Person\AdvisorAssignment;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Advisors see the students they currently advise.
 */
#[Layout('components.layouts.portal')]
class Advisees extends Component
{
    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $assignments = $user->person_id === null ? collect() : AdvisorAssignment::query()->current()
            ->whereHas('instructor', fn (Builder $instructor) => $instructor->where('person_id', $user->person_id))
            ->with(['studentRecord.person', 'studentRecord.program'])
            ->get()
            ->sortBy(fn (AdvisorAssignment $assignment) => $assignment->studentRecord->student_number);

        return view('livewire.portal.advisees', ['assignments' => $assignments])->title(__('portal.advisees.title'));
    }
}
