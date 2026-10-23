<?php

namespace App\Livewire\Portal;

use App\Domain\Grading\Services\GradeReport;
use App\Domain\Person\Services\CurrentStudentRecord;
use App\Models\Grading\TermRecord;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * A student's finalized grades by term with credits, GPA and standing (legacy stu/score.php).
 * A hold that blocks grades hides them, enforced here and not only in the menu (the legacy
 * greyed out the link but served the page).
 */
#[Layout('components.layouts.portal')]
class MyGrades extends Component
{
    public function render(CurrentStudentRecord $current, GradeReport $report): View
    {
        /** @var User $user */
        $user = Auth::user();
        $record = $current->get($user) ?? abort(404);
        $hold = $report->blockingHold($record);

        return view('livewire.portal.my-grades', [
            'record' => $record,
            'hold' => $hold,
            'terms' => $hold === null ? $report->terms($record) : collect(),
            'superseded' => $hold === null ? $report->superseded($record) : [],
            'latest' => $hold === null ? TermRecord::query()->where('student_record_id', $record->id)
                ->join('terms', 'terms.id', '=', 'term_records.term_id')->orderByDesc('terms.start_date')->first(['term_records.*']) : null,
        ])->title(__('grading.grades.title'));
    }
}
