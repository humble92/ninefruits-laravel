<?php

namespace Database\Seeders\Demo;

use App\Domain\Attendance\Actions\GenerateClassMeetings;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Term\Enums\TermStatus;
use App\Models\Attendance\AttendanceRecord;
use App\Models\Attendance\ClassMeeting;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Attendance for the current term: class meetings for every section, and the meetings held
 * so far recorded (mostly present, some late, absent and excused), the newest meeting of
 * one section in five left unrecorded. Written in bulk: thousands of rows, no audit.
 */
class DemoAttendanceSeeder extends Seeder
{
    public function run(GenerateClassMeetings $generate): void
    {
        $recorder = User::query()->where('email', 'academic-affairs@'.config('setup.demo.email_domain'))->first();
        $current = Term::query()->where('status', TermStatus::Published)->where('start_date', '<=', now()->toDateString())->orderByDesc('start_date')->first();

        if ($recorder === null || $current === null || ClassMeeting::query()->exists()) {
            return;
        }

        $generate->forTerm($current);
        $enrollments = Enrollment::query()->where('term_id', $current->id)->where('status', EnrollmentStatus::Enrolled)->get(['id', 'section_id'])->groupBy('section_id');
        $held = ClassMeeting::query()->whereIn('section_id', $enrollments->keys())->scheduled()->whereDate('meeting_date', '<=', CarbonImmutable::today())
            ->orderBy('meeting_date')->get(['id', 'section_id'])->groupBy('section_id');
        $now = CarbonImmutable::now();
        $rows = [];

        foreach ($held as $sectionId => $meetings) {
            $skipLast = $sectionId % 5 === 0;

            foreach ($meetings as $index => $meeting) {
                if ($skipLast && $index === $meetings->count() - 1) {
                    continue;
                }

                foreach ($enrollments->get($sectionId, collect()) as $enrollment) {
                    $rows[] = [
                        'class_meeting_id' => $meeting->id, 'enrollment_id' => $enrollment->id, 'recorded_by_id' => $recorder->id, 'recorded_at' => $now, 'created_at' => $now, 'updated_at' => $now,
                        'status' => match (true) {
                            mt_rand(1, 100) <= 85 => AttendanceStatus::Present->value,
                            mt_rand(1, 100) <= 50 => AttendanceStatus::Late->value,
                            mt_rand(1, 100) <= 70 => AttendanceStatus::Absent->value,
                            default => AttendanceStatus::Excused->value,
                        },
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            AttendanceRecord::query()->insert($chunk);
        }
    }
}
