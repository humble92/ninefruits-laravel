<?php

namespace App\Livewire\Portal;

use App\Domain\Attendance\Actions\GenerateClassMeetings;
use App\Domain\Attendance\Actions\ManageClassMeetings;
use App\Domain\Attendance\Actions\RecordAttendance;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Services\AttendanceSummary;
use App\Domain\Attendance\Services\SectionRoster;
use App\Models\Attendance\AttendanceRecord;
use App\Models\Attendance\ClassMeeting;
use App\Models\Offering\Section;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The instructor's classroom for a section: the roster with photos and attendance
 * totals, the class meetings, and attendance entry for one meeting (legacy prt_chuxi
 * printed an empty sheet; here it is recorded).
 */
#[Layout('components.layouts.portal')]
class SectionAttendance extends Component
{
    #[Locked]
    public int $sectionId;

    #[Url]
    public ?int $meetingId = null;

    /** @var array<int, string|null> enrollment id => status */
    public array $statuses = [];

    /** @var array<int, string|null> */
    public array $notes = [];

    public string $newDate = '';

    public string $newStart = '09:00';

    public string $newEnd = '10:50';

    public ?string $message = null;

    public string $messageTone = 'success';

    public function mount(Section $section): void
    {
        abort_unless(SectionRoster::canView($section, $this->user()), 403);
        $this->sectionId = $section->id;
        $this->newDate = CarbonImmutable::today()->toDateString();
        $this->fillStatuses();
    }

    public function open(?int $meetingId): void
    {
        $this->meetingId = $meetingId;
        $this->message = null;
        $this->fillStatuses();
    }

    public function markAll(string $status): void
    {
        foreach ($this->statuses as $id => $value) {
            $this->statuses[$id] = $status;
        }
    }

    public function save(RecordAttendance $record): void
    {
        $meeting = $this->meeting() ?? abort(404);
        $this->attempt(fn () => $record->save($meeting, $this->statuses, $this->user(), $this->notes), __('attendance.portal.saved'));
    }

    public function generate(GenerateClassMeetings $generate): void
    {
        $section = $this->section();
        $this->attempt(function () use ($generate, $section) {
            ManageClassMeetings::authorize($section, $this->user());
            $created = $generate->forSection($section, $this->user());
            $this->message = __('attendance.portal.generated', ['count' => $created]);
        }, null);
    }

    public function addMeeting(ManageClassMeetings $manage): void
    {
        $this->attempt(fn () => $manage->add($this->section(), CarbonImmutable::parse($this->newDate), $this->newStart, $this->newEnd, $this->user()), __('attendance.portal.meeting_added'));
    }

    public function cancelMeeting(int $meetingId, string $reason, ManageClassMeetings $manage): void
    {
        $meeting = ClassMeeting::query()->where('section_id', $this->sectionId)->findOrFail($meetingId);
        $this->attempt(fn () => $manage->cancel($meeting, $this->user(), $reason), __('attendance.portal.meeting_cancelled'));
    }

    public function restoreMeeting(int $meetingId, ManageClassMeetings $manage): void
    {
        $meeting = ClassMeeting::query()->where('section_id', $this->sectionId)->findOrFail($meetingId);
        $this->attempt(fn () => $manage->restore($meeting, $this->user()), __('attendance.portal.meeting_restored'));
    }

    public function render(SectionRoster $roster, AttendanceSummary $summary): View
    {
        $section = $this->section()->load(['course', 'version', 'term', 'session']);
        $enrollments = $roster->enrollments($section);
        $meetings = ClassMeeting::query()->where('section_id', $section->id)->with('room.building')->withCount('records')->orderBy('meeting_date')->orderBy('start_time')->get();

        return view('livewire.portal.section-attendance', [
            'section' => $section,
            'enrollments' => $enrollments,
            'meetings' => $meetings,
            'meeting' => $this->meeting(),
            'summary' => $summary->forSection($section, $enrollments),
            'statusOptions' => AttendanceStatus::cases(),
            'canEdit' => $section->isTaughtBy($this->user()) || $section->isManageableBy($this->user()),
        ])->title(__('attendance.portal.title').' · '.$section->label());
    }

    private function fillStatuses(): void
    {
        $this->statuses = [];
        $this->notes = [];
        $meeting = $this->meeting();

        if ($meeting === null) {
            return;
        }

        $records = AttendanceRecord::query()->where('class_meeting_id', $meeting->id)->get()->keyBy('enrollment_id');

        foreach (app(SectionRoster::class)->enrollments($this->section()) as $enrollment) {
            $this->statuses[$enrollment->id] = $records->get($enrollment->id)?->status->value;
            $this->notes[$enrollment->id] = $records->get($enrollment->id)?->note;
        }
    }

    private function meeting(): ?ClassMeeting
    {
        return $this->meetingId === null ? null : ClassMeeting::query()->where('section_id', $this->sectionId)->find($this->meetingId);
    }

    private function attempt(callable $callback, ?string $success): void
    {
        try {
            $callback();
            $this->messageTone = 'success';

            if ($success !== null) {
                $this->message = $success;
            }
        } catch (ValidationException $e) {
            [$this->message, $this->messageTone] = [implode(' ', array_merge(...array_values($e->errors()))), 'error'];
        } catch (AuthorizationException) {
            [$this->message, $this->messageTone] = [__('attendance.errors.forbidden'), 'error'];
        }
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
