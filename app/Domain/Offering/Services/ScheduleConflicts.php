<?php

namespace App\Domain\Offering\Services;

use App\Models\Offering\Section;
use App\Models\Offering\SectionMeeting;
use App\Models\Person\Instructor;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Runs a timetable change and turns the database's double-booking errors (room exclusion
 * constraint, instructor trigger, overlapping meetings of one section) into messages that
 * name the other section. The database decides; this only explains.
 */
class ScheduleConflicts
{
    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $change
     * @return TResult
     */
    public function guard(Closure $change, string $field = 'schedule'): mixed
    {
        try {
            // Own savepoint: a failed statement would otherwise abort the caller's transaction.
            return DB::transaction($change);
        } catch (QueryException $exception) {
            $message = $this->explain($exception);

            if ($message === null) {
                throw $exception;
            }

            throw ValidationException::withMessages([$field => $message]);
        }
    }

    public function explain(QueryException $exception): ?string
    {
        if ($exception->getCode() !== '23P01') {
            return null;
        }

        $text = $exception->getMessage();

        if (preg_match('/instructor_conflict instructor=(\d+) section=\d+ other_section=(\d+)/', $text, $match)) {
            $instructor = Instructor::query()->with('person')->find((int) $match[1]);

            return __('offering.errors.instructor_conflict', [
                'instructor' => $instructor?->person->displayName() ?? '#'.$match[1],
                'section' => $this->sectionLabel((int) $match[2]),
            ]);
        }

        if (str_contains($text, 'section_meetings_room_excl')) {
            return __('offering.errors.room_conflict', ['section' => $this->conflictingSectionFromDetail($text) ?? '-']);
        }

        if (str_contains($text, 'section_meetings_section_excl')) {
            return __('offering.errors.meetings_overlap');
        }

        return null;
    }

    /**
     * The DETAIL of an exclusion violation names the existing row's key, e.g.
     * "(room_id, weekday, time_range, date_range)=(5, 1, [540,615), ...) conflicts with existing key (...)=(5, 1, [570,630), [2026-09-01,2026-12-21))".
     */
    private function conflictingSectionFromDetail(string $text): ?string
    {
        if (! preg_match('/conflicts with existing key \(room_id, weekday, time_range, date_range\)=\((\d+), (\d+), \[(\d+),(\d+)\), \[([\d-]+),([\d-]+)\)\)/', $text, $match)) {
            return null;
        }

        $meeting = SectionMeeting::query()->where('room_id', (int) $match[1])->where('weekday', (int) $match[2])->where('is_active', true)
            ->whereRaw('time_range = int4range(?, ?)', [(int) $match[3], (int) $match[4]])
            ->whereRaw('date_range = daterange(?::date, ?::date)', [$match[5], $match[6]])
            ->first();

        return $meeting === null ? null : $this->sectionLabel($meeting->section_id);
    }

    private function sectionLabel(int $sectionId): string
    {
        $section = Section::query()->with(['course', 'term'])->find($sectionId);

        return $section === null ? '#'.$sectionId : $section->label().' ('.$section->term->code.')';
    }
}
