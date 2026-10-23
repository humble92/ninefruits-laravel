<?php

namespace App\Domain\Lms\Actions;

use App\Domain\Lms\Enums\LinkStatus;
use App\Domain\Lms\Enums\SyncOperation;
use App\Domain\Lms\Services\ConnectorRegistry;
use App\Domain\Lms\Services\SyncLogger;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Models\Lms\LmsCourseLink;
use App\Models\Lms\LmsPlatform;
use App\Models\Lms\LmsUserLink;
use App\Models\Offering\Section;
use App\Models\Registration\Enrollment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Pushes a section to a platform: the course, its instructors and its enrolled students.
 * Idempotent: links are created or kept, and every member is ensured again.
 */
class SyncSection
{
    public function __construct(private readonly ConnectorRegistry $connectors, private readonly SyncLogger $log) {}

    /**
     * @return array{course: string, members: int}
     */
    public function handle(LmsPlatform $platform, Section $section, bool $dryRun = false): array
    {
        $link = LmsCourseLink::query()->firstOrCreate(['lms_platform_id' => $platform->id, 'section_id' => $section->id]);
        $summary = ['course' => '', 'members' => 0];

        try {
            // Resolved inside: a driver without a connector marks the link failed like any other error.
            $connector = $this->connectors->for($platform);
            $this->log->run($platform, SyncOperation::CourseSync, ['section_id' => $section->id], function () use ($connector, $platform, $section, $link, &$summary) {
                $course = $connector->ensureCourse($platform, $section);
                $link->forceFill(['external_course_id' => $course['id'], 'external_url' => $course['url'], 'status' => LinkStatus::Linked, 'last_synced_at' => CarbonImmutable::now(), 'last_error' => null])->save();
                $summary['course'] = $course['id'];

                return $course;
            }, $dryRun);
        } catch (Throwable $e) {
            $link->forceFill(['status' => LinkStatus::Failed, 'last_error' => mb_substr($e->getMessage(), 0, 1000)])->save();

            throw $e;
        }

        if ($dryRun || ! $platform->should_sync_enrollments) {
            return $summary;
        }

        $section->loadMissing('instructors.person.user');

        foreach ($section->instructors as $instructor) {
            if ($instructor->person->user !== null) {
                $this->member($platform, $link, $instructor->person->user, 'instructor');
                $summary['members']++;
            }
        }

        foreach (Enrollment::query()->where('section_id', $section->id)->where('status', EnrollmentStatus::Enrolled)->with('studentRecord.person.user')->get() as $enrollment) {
            $user = $enrollment->studentRecord->person->user;

            if ($user !== null) {
                $this->member($platform, $link, $user, 'student', $enrollment);
                $summary['members']++;
            }
        }

        return $summary;
    }

    public function member(LmsPlatform $platform, LmsCourseLink $link, User $user, string $role, ?Enrollment $enrollment = null): void
    {
        $connector = $this->connectors->for($platform);
        $this->log->run($platform, SyncOperation::EnrollmentAdd, ['section_id' => $link->section_id, 'enrollment_id' => $enrollment?->id, 'user_id' => $user->id, 'payload' => ['role' => $role]], function () use ($connector, $platform, $link, $user, $role) {
            $userLink = LmsUserLink::query()->firstOrNew(['lms_platform_id' => $platform->id, 'user_id' => $user->id]);
            $userLink->external_user_id ??= $connector->ensureUser($platform, $user);
            $userLink->forceFill(['last_synced_at' => CarbonImmutable::now()])->save();
            $connector->enroll($platform, (string) $link->external_course_id, $userLink->external_user_id, $role);

            return ['external_user_id' => $userLink->external_user_id, 'role' => $role];
        });
    }

    public function removeMember(LmsPlatform $platform, LmsCourseLink $link, User $user, ?Enrollment $enrollment = null): void
    {
        $connector = $this->connectors->for($platform);
        $this->log->run($platform, SyncOperation::EnrollmentRemove, ['section_id' => $link->section_id, 'enrollment_id' => $enrollment?->id, 'user_id' => $user->id], function () use ($connector, $platform, $link, $user) {
            $userLink = LmsUserLink::query()->where('lms_platform_id', $platform->id)->where('user_id', $user->id)->first();

            if ($userLink === null || $link->external_course_id === null) {
                return 'not linked';
            }

            $connector->unenroll($platform, $link->external_course_id, $userLink->external_user_id);

            return ['external_user_id' => $userLink->external_user_id];
        });
    }
}
