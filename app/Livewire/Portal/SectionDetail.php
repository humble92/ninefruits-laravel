<?php

namespace App\Livewire\Portal;

use App\Domain\Lms\Enums\LinkStatus;
use App\Domain\Offering\Enums\SectionStatus;
use App\Models\Lms\LmsCourseLink;
use App\Models\Offering\Section;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * A section as students see it: course details, requisites, schedule and the published
 * syllabus. Instructors and staff also see drafts.
 */
#[Layout('components.layouts.portal')]
class SectionDetail extends Component
{
    public Section $section;

    public function mount(Section $section): void
    {
        /** @var User $user */
        $user = Auth::user();
        $canEdit = Gate::forUser($user)->allows('editSyllabus', $section);

        abort_unless($section->status === SectionStatus::Published || $canEdit, 404);

        $this->section = $section;
    }

    public function render(): View
    {
        $section = $this->section->load(['course', 'version.category', 'version.requisites.requiredCourse', 'session', 'term', 'instructors.person', 'meetings.room.building', 'syllabus']);
        $canEdit = Gate::allows('editSyllabus', $section);

        return view('livewire.portal.section-detail', [
            'section' => $section,
            'canEdit' => $canEdit,
            'syllabus' => $section->syllabus !== null && ($section->syllabus->published_at !== null || $canEdit) ? $section->syllabus : null,
            'lmsLinks' => LmsCourseLink::query()->where('section_id', $section->id)->where('status', LinkStatus::Linked)->whereNotNull('external_url')->with('platform')->get(),
        ])->title($section->label());
    }
}
