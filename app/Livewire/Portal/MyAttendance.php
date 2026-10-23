<?php

namespace App\Livewire\Portal;

use App\Domain\Attendance\Services\AttendanceSummary;
use App\Domain\Person\Services\CurrentStudentRecord;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Models\Attendance\AttendanceRecord;
use App\Models\Attendance\ClassMeeting;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The student's attendance per course for a term, with the records per meeting.
 */
#[Layout('components.layouts.portal')]
class MyAttendance extends Component
{
    #[Url]
    public ?int $termId = null;

    public function render(CurrentStudentRecord $current, AttendanceSummary $summary): View
    {
        /** @var User $user */
        $user = Auth::user();
        $record = $current->get($user) ?? abort(404);
        $terms = Term::query()->whereIn('id', Enrollment::query()->where('student_record_id', $record->id)->select('term_id'))->orderByDesc('start_date')->get();
        $this->termId ??= ($terms->first(fn (Term $term) => $term->start_date->lte(now())) ?? $terms->first())?->id;

        $enrollments = Enrollment::query()->where('student_record_id', $record->id)->where('term_id', $this->termId)
            ->whereIn('status', [EnrollmentStatus::Enrolled, EnrollmentStatus::Completed])
            ->with(['section.course', 'version'])->get();
        $rows = $enrollments->map(function (Enrollment $enrollment) use ($summary) {
            $meetings = ClassMeeting::query()->where('section_id', $enrollment->section_id)->orderBy('meeting_date')->orderBy('start_time')->get();
            $records = AttendanceRecord::query()->where('enrollment_id', $enrollment->id)->get()->keyBy('class_meeting_id');
            $held = $meetings->filter(fn (ClassMeeting $meeting) => AttendanceSummary::isHeld($meeting));

            return [
                'enrollment' => $enrollment,
                'counts' => $summary->counts($records->only($held->pluck('id')->all())->values(), $held->count()),
                'meetings' => $held->map(fn (ClassMeeting $meeting) => ['meeting' => $meeting, 'record' => $records->get($meeting->id)]),
            ];
        });

        return view('livewire.portal.my-attendance', ['terms' => $terms, 'rows' => $rows])->title(__('attendance.portal.my_title'));
    }
}
