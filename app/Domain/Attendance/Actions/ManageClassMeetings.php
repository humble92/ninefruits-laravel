<?php

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\Enums\ClassMeetingStatus;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Attendance\ClassMeeting;
use App\Models\Offering\Section;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Added meetings (make-up classes) and cancellations (holidays, instructor absence), by
 * the section's instructors or sections.manage staff in scope.
 */
class ManageClassMeetings
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function add(Section $section, CarbonImmutable $date, string $startTime, string $endTime, User $user, ?int $roomId = null, ?string $topic = null): ClassMeeting
    {
        self::authorize($section, $user);

        if ($startTime >= $endTime) {
            throw ValidationException::withMessages(['end_time' => __('attendance.errors.time_order')]);
        }

        $section->loadMissing('session');

        if ($date->lt($section->session->start_date) || $date->gt($section->session->end_date)) {
            throw ValidationException::withMessages(['meeting_date' => __('attendance.errors.outside_session')]);
        }

        try {
            // Inside a transaction (savepoint) so a duplicate does not abort an enclosing one.
            $meeting = DB::transaction(fn () => ClassMeeting::query()->create([
                'section_id' => $section->id, 'meeting_date' => $date, 'start_time' => $startTime, 'end_time' => $endTime, 'room_id' => $roomId, 'topic' => $topic,
            ]));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['meeting_date' => __('attendance.errors.duplicate_meeting')]);
        }

        $this->audit->record('attendance.meeting_added', $meeting, $topic, ['section_id' => $section->id, 'date' => $date->toDateString()], $user);

        return $meeting;
    }

    public function cancel(ClassMeeting $meeting, User $user, string $reason): ClassMeeting
    {
        $meeting->loadMissing('section');
        self::authorize($meeting->section, $user);

        if ($meeting->status === ClassMeetingStatus::Cancelled) {
            throw ValidationException::withMessages(['status' => __('attendance.errors.already_cancelled')]);
        }

        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => __('registration.errors.reason_required')]);
        }

        $meeting->forceFill(['status' => ClassMeetingStatus::Cancelled, 'cancel_reason' => $reason])->save();
        $this->audit->record('attendance.meeting_cancelled', $meeting, $reason, ['section_id' => $meeting->section_id, 'date' => $meeting->meeting_date->toDateString()], $user);

        return $meeting;
    }

    public function restore(ClassMeeting $meeting, User $user): ClassMeeting
    {
        $meeting->loadMissing('section');
        self::authorize($meeting->section, $user);
        $meeting->forceFill(['status' => ClassMeetingStatus::Scheduled, 'cancel_reason' => null])->save();
        $this->audit->record('attendance.meeting_restored', $meeting, null, ['section_id' => $meeting->section_id], $user);

        return $meeting;
    }

    public function setTopic(ClassMeeting $meeting, User $user, ?string $topic): ClassMeeting
    {
        $meeting->loadMissing('section');
        self::authorize($meeting->section, $user);
        $meeting->forceFill(['topic' => blank($topic) ? null : trim((string) $topic)])->save();

        return $meeting;
    }

    public static function authorize(Section $section, User $user): void
    {
        if (! $section->isTaughtBy($user) && ! $section->isManageableBy($user)) {
            throw new AuthorizationException;
        }
    }
}
