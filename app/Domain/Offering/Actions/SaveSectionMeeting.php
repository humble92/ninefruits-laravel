<?php

namespace App\Domain\Offering\Actions;

use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Offering\Services\ScheduleConflicts;
use App\Models\Offering\Section;
use App\Models\Offering\SectionMeeting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Adds or changes a weekly meeting. Dates default to the section's session; the database
 * refuses a room or instructor that is already booked, and the error names the other
 * section.
 */
class SaveSectionMeeting
{
    public function __construct(private readonly ScheduleConflicts $conflicts) {}

    /**
     * @param  array{weekday: int, start_time: string, end_time: string, room_id?: int|null, start_date?: string|null, end_date?: string|null}  $data
     */
    public function handle(Section $section, array $data, User $user, ?SectionMeeting $meeting = null): SectionMeeting
    {
        if (! $section->isManageableBy($user)) {
            throw new AuthorizationException;
        }

        $session = $section->session;
        $start = filled($data['start_date'] ?? null) ? CarbonImmutable::parse((string) $data['start_date']) : $session->start_date;
        $end = filled($data['end_date'] ?? null) ? CarbonImmutable::parse((string) $data['end_date']) : $session->end_date;

        if ($start->lessThan($session->start_date) || $end->greaterThan($session->end_date) || $end->lessThan($start)) {
            throw ValidationException::withMessages(['start_date' => __('offering.errors.meeting_outside_session')]);
        }

        if ($data['end_time'] <= $data['start_time']) {
            throw ValidationException::withMessages(['end_time' => __('offering.errors.meeting_time_order')]);
        }

        $meeting ??= new SectionMeeting(['section_id' => $section->id]);
        $meeting->fill([
            'weekday' => $data['weekday'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'room_id' => $data['room_id'] ?? null,
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $meeting->is_active = $section->status !== SectionStatus::Cancelled;

        return $this->conflicts->guard(fn () => tap($meeting)->save(), 'room_id');
    }
}
