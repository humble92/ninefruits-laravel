<?php

namespace App\Livewire\Portal;

use App\Domain\Evaluation\Services\EvaluationTasks;
use App\Domain\Grading\Services\GradeReport;
use App\Domain\Person\Services\CurrentStudentRecord;
use App\Models\Grading\TermRecord;
use App\Models\Term\Term;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * A student's finalized grades by term with credits, GPA and standing (legacy stu/score.php).
 * A hold that blocks grades hides them, and an open, unfinished course evaluation hides that
 * term's grades (setting) - enforced here, not only in the menu (the legacy greyed out the
 * link but served the page).
 */
#[Layout('components.layouts.portal')]
class MyGrades extends Component
{
    public function render(CurrentStudentRecord $current, GradeReport $report, EvaluationTasks $evaluations): View
    {
        /** @var User $user */
        $user = Auth::user();
        $record = $current->get($user) ?? abort(404);
        $hold = $report->blockingHold($record);
        // Terms whose course evaluation is open and unfinished (setting).
        $evaluationTerms = $evaluations->blockedTermIds($record);

        return view('livewire.portal.my-grades', [
            'record' => $record,
            'hold' => $hold,
            'terms' => $hold === null ? $report->terms($record, $evaluationTerms) : collect(),
            'evaluationTerms' => Term::query()->whereKey($evaluationTerms)->get(),
            'superseded' => $hold === null ? $report->superseded($record) : [],
            'latest' => $hold === null ? TermRecord::query()->where('student_record_id', $record->id)->whereNotIn('term_id', $evaluationTerms)
                ->join('terms', 'terms.id', '=', 'term_records.term_id')->orderByDesc('terms.start_date')->first(['term_records.*']) : null,
        ])->title(__('grading.grades.title'));
    }
}
