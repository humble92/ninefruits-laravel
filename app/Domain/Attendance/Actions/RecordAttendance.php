<?php

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Enums\ClassMeetingStatus;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Attendance\AttendanceRecord;
use App\Models\Attendance\ClassMeeting;
use App\Models\Registration\Enrollment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Attendance for one meeting: a status per enrolled student (present, late, absent,
 * excused), entered by the instructors or staff. Entering again replaces; the audit record
 * keeps the previous statuses.
 */
class RecordAttendance
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<int, string|null>  $statuses  enrollment id => status value (null clears)
     * @param  array<int, string|null>  $notes  enrollment id => note
     * @return int records written
     */
    public function save(ClassMeeting $meeting, array $statuses, User $user, array $notes = []): int
    {
        $meeting->loadMissing('section');
        ManageClassMeetings::authorize($meeting->section, $user);

        if ($meeting->status === ClassMeetingStatus::Cancelled) {
            throw ValidationException::withMessages(['meeting' => __('attendance.errors.meeting_cancelled')]);
        }

        if ($meeting->meeting_date->gt(CarbonImmutable::today())) {
            throw ValidationException::withMessages(['meeting' => __('attendance.errors.meeting_future')]);
        }

        $enrollmentIds = Enrollment::query()->where('section_id', $meeting->section_id)
            ->whereIn('status', [EnrollmentStatus::Enrolled, EnrollmentStatus::Completed])->pluck('id')->flip();
        $before = AttendanceRecord::query()->where('class_meeting_id', $meeting->id)->get()->keyBy('enrollment_id');
        $changes = [];
        $written = 0;

        DB::transaction(function () use ($meeting, $statuses, $notes, $user, $enrollmentIds, $before, &$changes, &$written) {
            foreach ($statuses as $enrollmentId => $value) {
                if (! $enrollmentIds->has($enrollmentId)) {
                    throw ValidationException::withMessages(['statuses' => __('attendance.errors.not_enrolled')]);
                }

                $previous = $before->get($enrollmentId);

                if ($value === null || $value === '') {
                    if ($previous !== null) {
                        $previous->delete();
                        $changes[$enrollmentId] = [$previous->status->value, null];
                    }

                    continue;
                }

                $status = AttendanceStatus::tryFrom($value) ?? throw ValidationException::withMessages(['statuses' => __('attendance.errors.invalid_status')]);
                $note = isset($notes[$enrollmentId]) && trim((string) $notes[$enrollmentId]) !== '' ? mb_substr(trim((string) $notes[$enrollmentId]), 0, 200) : null;

                if ($previous !== null && $previous->status === $status && $previous->note === $note) {
                    continue;
                }

                AttendanceRecord::query()->updateOrCreate(
                    ['class_meeting_id' => $meeting->id, 'enrollment_id' => $enrollmentId],
                    ['status' => $status, 'note' => $note, 'recorded_by_id' => $user->id, 'recorded_at' => CarbonImmutable::now()],
                );
                $changes[$enrollmentId] = [$previous?->status->value, $status->value];
                $written++;
            }
        });

        if ($changes !== []) {
            $this->audit->record('attendance.recorded', $meeting, null, ['section_id' => $meeting->section_id, 'date' => $meeting->meeting_date->toDateString(), 'changes' => $changes], $user);
        }

        return $written;
    }
}
