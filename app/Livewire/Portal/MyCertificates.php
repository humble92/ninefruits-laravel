<?php

namespace App\Livewire\Portal;

use App\Domain\Person\Services\CurrentStudentRecord;
use App\Models\Certificate\CertificateIssue;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The certificates issued to the student (staff issue them; docs/porting-plan.md 17.5).
 */
#[Layout('components.layouts.portal')]
class MyCertificates extends Component
{
    public function render(CurrentStudentRecord $current): View
    {
        /** @var User $user */
        $user = Auth::user();
        $record = $current->get($user) ?? abort(404);

        return view('livewire.portal.my-certificates', [
            'issues' => CertificateIssue::query()->where('student_record_id', $record->id)->with('type')->orderByDesc('issued_at')->get(),
        ])->title(__('certificate.portal.title'));
    }
}
