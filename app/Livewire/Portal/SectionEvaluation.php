<?php

namespace App\Livewire\Portal;

use App\Domain\Evaluation\Services\EvaluationResults;
use App\Models\Offering\Section;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * A section's course evaluation results for its instructors (after publication) and staff
 * with evaluation access (legacy counsel/professor_view.php, which showed any section's
 * results and comments to any professor).
 */
#[Layout('components.layouts.portal')]
class SectionEvaluation extends Component
{
    public Section $section;

    public function mount(Section $section, EvaluationResults $results): void
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($results->canView($section, $user), 403);

        $this->section = $section->load(['course', 'version', 'term']);
    }

    public function render(EvaluationResults $results): View
    {
        return view('livewire.portal.section-evaluation', ['result' => $results->forSection($this->section)])
            ->title(__('evaluation.results.title').' '.$this->section->label());
    }
}
