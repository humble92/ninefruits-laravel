<?php

namespace App\Livewire\Portal;

use App\Domain\Graduation\Services\DegreeAudit;
use App\Domain\Person\Services\CurrentStudentRecord;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The student's own degree audit, evaluated now (not the stored one): what is done and
 * what is left per requirement.
 */
#[Layout('components.layouts.portal')]
class MyGraduation extends Component
{
    public function render(CurrentStudentRecord $current, DegreeAudit $audit): View
    {
        /** @var User $user */
        $user = Auth::user();
        $record = $current->get($user) ?? abort(404);

        return view('livewire.portal.my-graduation', [
            'record' => $record,
            'results' => array_map(fn ($result) => $result->toArray(), $audit->evaluateAll($record)),
        ])->title(__('graduation.portal.title'));
    }
}
