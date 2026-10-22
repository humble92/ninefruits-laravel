<?php

namespace App\Console\Commands\Offering;

use App\Console\Commands\AuditedCommand;
use App\Domain\Offering\Enums\SectionStatus;
use App\Models\Offering\Section;
use App\Models\Offering\SectionMeeting;
use App\Models\Term\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Checks a term's sections before publishing the timetable. Double bookings are refused by
 * the database already; this also finds what the database allows but people should fix.
 */
#[Signature('schedule:check-conflicts {term : Term code, e.g. 2027-SP}')]
#[Description('Check a term\'s sections: double bookings, rooms too small, versions, instructors, seat groups')]
class CheckScheduleConflicts extends Command
{
    public function handle(): int
    {
        $term = Term::query()->where('code', (string) $this->argument('term'))->first();

        if ($term === null) {
            $this->components->error('Unknown term code.');

            return self::FAILURE;
        }

        $sections = Section::query()->where('term_id', $term->id)->where('status', '!=', SectionStatus::Cancelled)
            ->with(['course', 'version', 'session', 'instructorAssignments.instructor', 'meetings.room', 'seatGroups'])
            ->get();

        $problems = [];
        $notes = [];

        foreach ($sections as $section) {
            $label = $section->label();

            if (! $section->version->isEffectiveOn($section->session->start_date)) {
                $problems[] = [$label, 'course version not in effect when the session starts'];
            }

            if ($section->seatGroups->sum('capacity') > $section->capacity) {
                $problems[] = [$label, 'seat groups exceed the capacity'];
            }

            foreach ($section->instructorAssignments as $assignment) {
                if (! $assignment->instructor->isCurrent($section->session->start_date)) {
                    $problems[] = [$label, "instructor {$assignment->instructor->employee_number} is no longer employed"];
                }
            }

            foreach ($section->meetings as $meeting) {
                /** @var SectionMeeting $meeting */
                if ($meeting->room !== null && $meeting->room->capacity !== null && $meeting->room->capacity < $section->capacity) {
                    $problems[] = [$label, "room {$meeting->room->code} holds {$meeting->room->capacity}, section capacity {$section->capacity}"];
                }

                if ($meeting->start_date->lessThan($section->session->start_date) || $meeting->end_date->greaterThan($section->session->end_date)) {
                    $problems[] = [$label, 'meeting dates outside the session'];
                }

                if ($meeting->room_id === null) {
                    $notes[] = [$label, 'meeting '.$meeting->timeLabel().' has no room'];
                }
            }

            if ($section->instructorAssignments->isEmpty()) {
                $notes[] = [$label, 'no instructor'];
            }

            if ($section->meetings->isEmpty()) {
                $notes[] = [$label, 'no meeting times'];
            }
        }

        // Safety net for rows written while constraints were off (e.g. a restore).
        $doubleBookings = DB::select(<<<'SQL'
            SELECT a.section_id AS first, b.section_id AS second
            FROM section_meetings AS a
            JOIN section_meetings AS b ON a.id < b.id AND a.room_id = b.room_id AND a.weekday = b.weekday
                AND a.time_range && b.time_range AND a.date_range && b.date_range
            JOIN sections AS s ON s.id = a.section_id
            WHERE s.term_id = ? AND a.is_active AND b.is_active
            SQL, [$term->id]);

        foreach ($doubleBookings as $row) {
            $problems[] = ['#'.$row->first.' / #'.$row->second, 'room double-booked'];
        }

        if ($notes !== []) {
            $this->table(['Section', 'Note'], $notes);
        }

        if ($problems === []) {
            $this->components->info(sprintf('%d section(s) checked, no problems.', $sections->count()));

            return self::SUCCESS;
        }

        $this->table(['Section', 'Problem'], $problems);
        $this->components->warn(count($problems).' problem(s).');

        return AuditedCommand::PROBLEMS_FOUND;
    }
}
