<?php

namespace App\Domain\Lms\Actions;

use App\Domain\Lms\Enums\LinkStatus;
use App\Models\Lms\LmsCourseLink;
use App\Models\Lms\LmsPlatform;
use App\Models\Registration\Enrollment;

/**
 * One enrollment added to or removed from the platform course. A section not yet linked is
 * synchronized whole first.
 */
class SyncEnrollment
{
    public function __construct(private readonly SyncSection $sections) {}

    public function added(LmsPlatform $platform, Enrollment $enrollment): void
    {
        $enrollment->loadMissing(['section', 'studentRecord.person.user']);
        $link = LmsCourseLink::query()->where('lms_platform_id', $platform->id)->where('section_id', $enrollment->section_id)->first();

        if ($link === null || $link->status !== LinkStatus::Linked) {
            $this->sections->handle($platform, $enrollment->section);

            return;
        }

        $user = $enrollment->studentRecord->person->user;

        if ($user !== null && $platform->should_sync_enrollments) {
            $this->sections->member($platform, $link, $user, 'student', $enrollment);
        }
    }

    public function removed(LmsPlatform $platform, Enrollment $enrollment): void
    {
        $enrollment->loadMissing(['studentRecord.person.user']);
        $link = LmsCourseLink::query()->where('lms_platform_id', $platform->id)->where('section_id', $enrollment->section_id)->first();
        $user = $enrollment->studentRecord->person->user;

        if ($link !== null && $user !== null && $platform->should_sync_enrollments) {
            $this->sections->removeMember($platform, $link, $user, $enrollment);
        }
    }
}
