<?php

namespace App\Livewire\Portal;

use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Term\Services\TermResolver;
use App\Models\Offering\Section;
use App\Models\Organization\OrgUnit;
use App\Models\Term\Term;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Published sections of a term with times, rooms, instructors and syllabi. Students plan
 * here before registration (Phase 5).
 */
#[Layout('components.layouts.portal')]
class CourseCatalog extends Component
{
    use WithPagination;

    #[Url]
    public ?int $termId = null;

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $orgUnitId = null;

    public function mount(TermResolver $resolver): void
    {
        $this->termId ??= $resolver->inSession()->first()->id
            ?? Term::query()->where('start_date', '>=', now()->toDateString())->orderBy('start_date')->value('id');
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['termId', 'search', 'orgUnitId'], true)) {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        $sections = Section::query()
            ->published()
            ->where('term_id', $this->termId)
            ->when($this->orgUnitId !== null, fn (Builder $query) => $query->where('org_unit_id', $this->orgUnitId))
            ->when(trim($this->search) !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereHas('course', fn (Builder $course) => $course->where('code', 'ilike', trim($this->search).'%'))
                ->orWhereHas('version', fn (Builder $version) => $version->whereRaw('util.i18n_search_text(title) ILIKE ?', ['%'.trim($this->search).'%']))))
            ->with(['course', 'version.category', 'session', 'instructors.person', 'meetings.room.building', 'syllabus'])
            ->join('courses', 'courses.id', '=', 'sections.course_id')
            ->orderBy('courses.code')->orderBy('sections.section_code')
            ->select('sections.*')
            ->paginate(25);

        return view('livewire.portal.course-catalog', [
            'sections' => $sections,
            'terms' => Term::query()->whereHas('sections', fn (Builder $section) => $section->where('status', SectionStatus::Published))->orderByDesc('start_date')->get(),
            'units' => OrgUnit::query()->whereIn('id', Section::query()->published()->where('term_id', $this->termId)->select('org_unit_id'))->get()->sortBy('name'),
        ])->title(__('offering.catalog.title'));
    }
}
