<?php

namespace App\Domain\Attendance\Services;

use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Enums\ClassMeetingStatus;
use App\Models\Attendance\AttendanceRecord;
use App\Models\Attendance\ClassMeeting;
use App\Models\Offering\Section;
use App\Models\Registration\Enrollment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Counts per student: held meetings (scheduled, on or before today), present, late,
 * absent, excused, not recorded, and the attendance rate (present + late + excused over
 * held). The institution's own rule (e.g. absences over a third fail) is applied by
 * people, not here.
 */
class AttendanceSummary
{
    /**
     * @param  iterable<int, Enrollment>  $enrollments
     * @return array{held: int, students: array<int, array{present: int, late: int, absent: int, excused: int, missing: int, rate: float|null}>}
     */
    public function forSection(Section $section, iterable $enrollments, ?CarbonImmutable $asOf = null): array
    {
        $asOf ??= CarbonImmutable::today();
        $held = ClassMeeting::query()->where('section_id', $section->id)->scheduled()->whereDate('meeting_date', '<=', $asOf)->pluck('id');
        $records = AttendanceRecord::query()->whereIn('class_meeting_id', $held)->get()->groupBy('enrollment_id');
        $students = [];

        foreach ($enrollments as $enrollment) {
            $students[$enrollment->id] = $this->counts($records->get($enrollment->id, collect()), $held->count());
        }

        return ['held' => $held->count(), 'students' => $students];
    }

    /**
     * @param  Collection<int, AttendanceRecord>  $records
     * @return array{present: int, late: int, absent: int, excused: int, missing: int, rate: float|null}
     */
    public function counts(Collection $records, int $held): array
    {
        $counts = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0];

        foreach ($records as $record) {
            $counts[$record->status->value]++;
        }

        $recorded = array_sum($counts);

        return [...$counts, 'missing' => max(0, $held - $recorded), 'rate' => $held === 0 ? null : round(($counts['present'] + $counts['late'] + $counts['excused']) / $held * 100, 1)];
    }

    public static function isCountedPresent(AttendanceStatus $status): bool
    {
        return $status !== AttendanceStatus::Absent;
    }

    public static function isHeld(ClassMeeting $meeting, ?CarbonImmutable $asOf = null): bool
    {
        return $meeting->status === ClassMeetingStatus::Scheduled && $meeting->meeting_date->lte($asOf ?? CarbonImmutable::today());
    }
}
