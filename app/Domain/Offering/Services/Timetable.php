<?php

namespace App\Domain\Offering\Services;

use App\Models\Offering\SectionMeeting;
use Illuminate\Support\Collection;

/**
 * Turns meetings into a weekly timetable: one column per weekday that has meetings (Monday
 * to Friday always), rows from the earliest start to the latest end in 30-minute steps.
 * Used by the instructor, room and (Phase 5) student timetables.
 */
class Timetable
{
    public const int STEP_MINUTES = 30;

    /**
     * @param  iterable<SectionMeeting>  $meetings
     * @param  callable(SectionMeeting): array{0: string, 1: string}  $describe  Title and subtitle of an entry
     * @return array{weekdays: list<int>, start: int, end: int, rows: list<int>, entries: list<array{weekday: int, start: int, end: int, top: int, span: int, title: string, subtitle: string, time: string}>}
     */
    public function build(iterable $meetings, callable $describe): array
    {
        $meetings = Collection::make($meetings)->filter(fn (SectionMeeting $meeting) => $meeting->is_active)->values();
        $weekdays = $meetings->pluck('weekday')->merge([1, 2, 3, 4, 5])->unique()->sort()->values()->all();
        $start = (int) ($meetings->map(fn (SectionMeeting $meeting) => $meeting->startMinute())->min() ?? 9 * 60);
        $end = (int) ($meetings->map(fn (SectionMeeting $meeting) => $meeting->endMinute())->max() ?? 18 * 60);
        $start = intdiv($start, self::STEP_MINUTES) * self::STEP_MINUTES;
        $end = (int) (ceil($end / self::STEP_MINUTES) * self::STEP_MINUTES);

        return [
            'weekdays' => $weekdays,
            'start' => $start,
            'end' => $end,
            'rows' => range($start, $end - self::STEP_MINUTES, self::STEP_MINUTES),
            'entries' => $meetings->map(fn (SectionMeeting $meeting) => [
                'weekday' => $meeting->weekday,
                'start' => $meeting->startMinute(),
                'end' => $meeting->endMinute(),
                'top' => intdiv($meeting->startMinute() - $start, self::STEP_MINUTES),
                'span' => max(1, (int) ceil(($meeting->endMinute() - $meeting->startMinute()) / self::STEP_MINUTES)),
                'title' => $describe($meeting)[0],
                'subtitle' => $describe($meeting)[1],
                'time' => $meeting->timeLabel(),
            ])->sortBy(['weekday', 'start'])->values()->all(),
        ];
    }

    /**
     * "Mon 09:00–10:15 E-201, Wed 09:00–10:15 E-201".
     *
     * @param  iterable<SectionMeeting>  $meetings  With room.building loaded
     */
    public static function summary(iterable $meetings): string
    {
        return Collection::make($meetings)
            ->map(fn (SectionMeeting $meeting) => trim(self::weekdayLabel($meeting->weekday).' '.$meeting->timeLabel().' '.self::roomLabel($meeting)))
            ->implode(', ');
    }

    public static function weekdayLabel(int $weekday): string
    {
        return __('offering.weekdays.'.$weekday);
    }

    public static function roomLabel(SectionMeeting $meeting): string
    {
        return $meeting->room === null ? '' : $meeting->room->building->code.'-'.$meeting->room->code;
    }
}
