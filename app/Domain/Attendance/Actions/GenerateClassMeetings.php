<?php

namespace App\Domain\Attendance\Actions;

use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Attendance\ClassMeeting;
use App\Models\Offering\Section;
use App\Models\Offering\SectionMeeting;
use App\Models\Term\Term;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Class meetings from the weekly meeting times: one per occurrence of the weekday between
 * the meeting time's start and end dates. Idempotent: existing meetings (same date and
 * start time) are kept, with their attendance.
 */
class GenerateClassMeetings
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @return int meetings created
     */
    public function forSection(Section $section, ?User $actor = null): int
    {
        if ($section->status === SectionStatus::Cancelled) {
            return 0;
        }

        $section->loadMissing('meetings');
        $existing = ClassMeeting::query()->where('section_id', $section->id)->get()
            ->map(fn (ClassMeeting $meeting) => $meeting->meeting_date->toDateString().' '.substr($meeting->start_time, 0, 5))->flip();
        $created = 0;

        DB::transaction(function () use ($section, $existing, &$created) {
            foreach ($section->meetings as $pattern) {
                /** @var SectionMeeting $pattern */
                if (! $pattern->is_active) {
                    continue;
                }

                foreach (CarbonPeriod::create($pattern->start_date, $pattern->end_date) as $day) {
                    $date = CarbonImmutable::instance($day);

                    if ($date->isoWeekday() !== $pattern->weekday || $existing->has($date->toDateString().' '.substr($pattern->start_time, 0, 5))) {
                        continue;
                    }

                    ClassMeeting::query()->create([
                        'section_id' => $section->id, 'section_meeting_id' => $pattern->id, 'meeting_date' => $date,
                        'start_time' => $pattern->start_time, 'end_time' => $pattern->end_time, 'room_id' => $pattern->room_id,
                    ]);
                    $created++;
                }
            }
        });

        if ($created > 0 && $actor !== null) {
            $this->audit->record('attendance.meetings_generated', $section, null, ['created' => $created], $actor);
        }

        return $created;
    }

    /**
     * @return array{sections: int, created: int}
     */
    public function forTerm(Term $term, ?User $actor = null): array
    {
        $summary = ['sections' => 0, 'created' => 0];

        foreach (Section::query()->where('term_id', $term->id)->where('status', '!=', SectionStatus::Cancelled)->with('meetings')->cursor() as $section) {
            $created = $this->forSection($section, null);
            $summary['sections']++;
            $summary['created'] += $created;
        }

        if ($actor !== null) {
            $this->audit->record('attendance.meetings_generated', $term, null, $summary, $actor);
        }

        return $summary;
    }
}
