<?php

namespace App\Livewire\Portal;

use App\Domain\Lms\Enums\LinkStatus;
use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Offering\Services\Timetable;
use App\Models\Evaluation\TermEvaluation;
use App\Models\Lms\LmsCourseLink;
use App\Models\Offering\Section;
use App\Models\Offering\SectionMeeting;
use App\Models\Term\Term;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * An instructor's sections and weekly timetable for a term, with links to the syllabi.
 */
#[Layout('components.layouts.portal')]
class MyTeaching extends Component
{
    #[Url]
    public ?int $termId = null;

    public function mount(): void
    {
        abort_if($this->user()->person_id === null, 404);

        $this->termId ??= $this->terms()->first(fn (Term $term) => $term->end_date->isFuture())->id ?? $this->terms()->last()?->id;
    }

    public function render(Timetable $timetable): View
    {
        $sections = Section::query()->taughtBy((int) $this->user()->person_id)->where('term_id', $this->termId)
            ->where('status', '!=', SectionStatus::Cancelled)
            ->with(['course', 'version', 'session', 'meetings.room.building', 'syllabus'])
            ->get()->sortBy(fn (Section $section) => $section->label());

        return view('livewire.portal.my-teaching', [
            'terms' => $this->terms(),
            'sections' => $sections,
            'publishedTermIds' => TermEvaluation::query()->whereNotNull('results_published_at')->pluck('term_id')->all(),
            'lmsLinks' => LmsCourseLink::query()->whereIn('section_id', $sections->pluck('id'))->where('status', LinkStatus::Linked)->whereNotNull('external_url')->get()->groupBy('section_id'),
            'timetable' => $timetable->build($sections->flatMap(fn (Section $section) => $section->meetings), fn (SectionMeeting $meeting) => [
                $sections->firstWhere('id', $meeting->section_id)?->label() ?? '',
                Timetable::roomLabel($meeting),
            ]),
        ])->title(__('offering.teaching.title'));
    }

    /**
     * @return Collection<int, Term>
     */
    private function terms(): Collection
    {
        return Term::query()->whereHas('sections', fn (Builder $section) => $section->taughtBy((int) $this->user()->person_id))->orderBy('start_date')->get();
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
