<?php

namespace App\Livewire\Portal;

use App\Domain\Offering\Actions\UpdateSyllabus;
use App\Models\Offering\Section;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Instructors of a section (and staff who manage it) write its syllabus.
 */
#[Layout('components.layouts.portal')]
class SyllabusEditor extends Component
{
    #[Locked]
    public int $sectionId;

    public string $language = '';

    /** @var array<string, mixed> */
    public array $content = ['overview' => '', 'objectives' => '', 'materials' => '', 'policies' => '', 'assessment' => [], 'weekly_plan' => []];

    public bool $published = false;

    public function mount(Section $section): void
    {
        abort_unless(Gate::allows('editSyllabus', $section), 403);

        $this->sectionId = $section->id;
        $syllabus = $section->syllabus;
        $this->language = $syllabus->language ?? ($section->instruction_language ?? app()->getLocale());
        $this->content = [...$this->content, ...($syllabus->content ?? [])];
        $this->published = $syllabus?->published_at !== null;

        if ($this->content['weekly_plan'] === []) {
            $weeks = (int) ceil(($section->session->start_date->diffInDays($section->session->end_date) + 1) / 7);
            $this->content['weekly_plan'] = array_map(fn (int $week) => ['week' => $week, 'topic' => ''], range(1, max(1, min(30, $weeks))));
        }
    }

    public function addAssessment(): void
    {
        $this->content['assessment'][] = ['item' => '', 'weight' => null];
    }

    public function removeAssessment(int $index): void
    {
        unset($this->content['assessment'][$index]);
        $this->content['assessment'] = array_values($this->content['assessment']);
    }

    public function save(UpdateSyllabus $update): void
    {
        $content = $this->content;
        $content['weekly_plan'] = array_values(array_filter($content['weekly_plan'], fn (array $week) => trim((string) $week['topic']) !== ''));
        $content['assessment'] = array_values(array_filter($content['assessment'], fn (array $item) => trim((string) $item['item']) !== ''));

        $update->handle($this->section(), $this->language, $content, $this->user(), $this->published);
        session()->flash('status', __('offering.syllabus.saved'));
    }

    public function render(): View
    {
        $section = $this->section()->load(['course', 'version', 'term', 'session']);

        return view('livewire.portal.syllabus-editor', ['section' => $section])->title(__('offering.syllabus.edit').' '.$section->label());
    }

    private function section(): Section
    {
        return Section::query()->findOrFail($this->sectionId);
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
