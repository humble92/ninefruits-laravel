<?php

namespace App\Domain\Lms\Actions;

use App\Domain\Lms\Enums\SyncOperation;
use App\Domain\Lms\Services\ConnectorRegistry;
use App\Domain\Lms\Services\SyncLogger;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Models\Lms\LmsCourseLink;
use App\Models\Lms\LmsPlatform;
use App\Models\Lms\LmsUserLink;
use App\Models\Offering\Section;
use App\Models\Registration\Enrollment;

/**
 * Final scores from the platform, mapped to enrollments. They are only a draft: the
 * instructor loads them into the grade entry screen, checks and submits
 * (docs/porting-plan.md section 12).
 */
class PullGrades
{
    public function __construct(private readonly ConnectorRegistry $connectors, private readonly SyncLogger $log) {}

    /**
     * @return array<int, float> enrollment id => score
     */
    public function handle(LmsPlatform $platform, Section $section): array
    {
        $link = LmsCourseLink::query()->where('lms_platform_id', $platform->id)->where('section_id', $section->id)->first();

        if ($link === null || $link->external_course_id === null || ! $platform->should_pull_grades) {
            return [];
        }

        $connector = $this->connectors->for($platform);
        $scores = [];

        $this->log->run($platform, SyncOperation::GradesPull, ['section_id' => $section->id], function () use ($connector, $platform, $link, $section, &$scores) {
            $byExternal = $connector->finalScores($platform, (string) $link->external_course_id);
            $users = LmsUserLink::query()->where('lms_platform_id', $platform->id)->whereIn('external_user_id', array_keys($byExternal))->pluck('user_id', 'external_user_id');
            $enrollments = Enrollment::query()->where('section_id', $section->id)->where('status', EnrollmentStatus::Enrolled)->with('studentRecord.person.user')->get();

            foreach ($enrollments as $enrollment) {
                $userId = $enrollment->studentRecord->person->user?->id;
                $external = $userId === null ? null : $users->search($userId);

                if ($external !== false && $external !== null && isset($byExternal[$external])) {
                    $scores[$enrollment->id] = (float) $byExternal[$external];
                }
            }

            return ['scores' => count($scores), 'reported' => count($byExternal)];
        });

        return $scores;
    }
}
