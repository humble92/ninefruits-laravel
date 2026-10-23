<?php

namespace App\Domain\Lms\Actions;

use App\Domain\Lms\Enums\LinkStatus;
use App\Domain\Lms\Enums\SyncOperation;
use App\Domain\Lms\Services\ConnectorRegistry;
use App\Domain\Lms\Services\SyncLogger;
use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Models\Lms\LmsCourseLink;
use App\Models\Lms\LmsPlatform;
use App\Models\Lms\LmsUserLink;
use App\Models\Offering\Section;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\User;

/**
 * The nightly full comparison: for every published section of the current terms, the
 * course exists and its members on the platform match the roster; missing students are
 * added, extra ones removed, and the differences are logged.
 */
class ReconcilePlatform
{
    public function __construct(private readonly ConnectorRegistry $connectors, private readonly SyncSection $sections, private readonly SyncLogger $log) {}

    /**
     * @return array{sections: int, added: int, removed: int, failed: int}
     */
    public function handle(LmsPlatform $platform, ?Term $term = null, bool $dryRun = false): array
    {
        $connector = $this->connectors->for($platform);
        $summary = ['sections' => 0, 'added' => 0, 'removed' => 0, 'failed' => 0];
        $sections = Section::query()->where('status', SectionStatus::Published)
            ->when($term !== null, fn ($q) => $q->where('term_id', $term->id), fn ($q) => $q->whereHas('term', fn ($t) => $t->where('end_date', '>=', now()->toDateString())))
            ->with('instructors.person.user')->cursor();

        foreach ($sections as $section) {
            $summary['sections']++;

            try {
                $link = LmsCourseLink::query()->where('lms_platform_id', $platform->id)->where('section_id', $section->id)->first();

                if ($link === null || $link->status !== LinkStatus::Linked) {
                    $this->sections->handle($platform, $section, $dryRun);

                    continue;
                }

                $expected = $this->expectedMembers($platform, $section);
                $actual = $connector->members($platform, (string) $link->external_course_id);
                $missing = array_diff_key($expected, $actual);
                $extra = array_diff_key($actual, $expected);

                $this->log->run($platform, SyncOperation::Reconcile, ['section_id' => $section->id, 'payload' => ['missing' => array_keys($missing), 'extra' => array_keys($extra)]], function () use ($platform, $link, $missing, $extra, $dryRun, &$summary) {
                    if ($dryRun) {
                        return ['missing' => array_keys($missing), 'extra' => array_keys($extra), 'dry_run' => true];
                    }

                    foreach ($missing as [$user, $role, $enrollment]) {
                        $this->sections->member($platform, $link, $user, $role, $enrollment);
                        $summary['added']++;
                    }

                    foreach (array_keys($extra) as $externalId) {
                        $userLink = LmsUserLink::query()->where('lms_platform_id', $platform->id)->where('external_user_id', $externalId)->with('user')->first();

                        if ($userLink !== null) {
                            $this->sections->removeMember($platform, $link, $userLink->user);
                            $summary['removed']++;
                        }
                    }

                    return ['missing' => array_keys($missing), 'extra' => array_keys($extra)];
                }, $dryRun);
            } catch (\Throwable) {
                $summary['failed']++;
            }
        }

        return $summary;
    }

    /**
     * external user id => [user, role, enrollment|null] for everyone who should be in the course.
     *
     * @return array<string, array{0: User, 1: string, 2: Enrollment|null}>
     */
    private function expectedMembers(LmsPlatform $platform, Section $section): array
    {
        $connector = $this->connectors->for($platform);
        $expected = [];

        foreach ($section->instructors as $instructor) {
            if (($user = $instructor->person->user) !== null) {
                $expected[$this->externalId($platform, $user->id) ?? $connector->ensureUser($platform, $user)] = [$user, 'instructor', null];
            }
        }

        foreach (Enrollment::query()->where('section_id', $section->id)->where('status', EnrollmentStatus::Enrolled)->with('studentRecord.person.user')->get() as $enrollment) {
            if (($user = $enrollment->studentRecord->person->user) !== null) {
                $expected[$this->externalId($platform, $user->id) ?? $connector->ensureUser($platform, $user)] = [$user, 'student', $enrollment];
            }
        }

        return $expected;
    }

    private function externalId(LmsPlatform $platform, int $userId): ?string
    {
        return LmsUserLink::query()->where('lms_platform_id', $platform->id)->where('user_id', $userId)->value('external_user_id');
    }
}
