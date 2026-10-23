<?php

namespace App\Domain\Lms\Contracts;

use App\Models\Lms\LmsPlatform;
use App\Models\Offering\Section;
use App\Models\User;

/**
 * What a platform connector does (docs/porting-plan.md section 12). The academic system
 * owns users, sections, rosters and final grades; the LMS owns content and raw scores.
 * Implementations talk to one platform; they get the platform (credentials, options) with
 * every call and must not keep state.
 */
interface LmsConnector
{
    /**
     * Creates or finds the course for the section; returns its id and URL on the platform.
     *
     * @return array{id: string, url: string|null}
     */
    public function ensureCourse(LmsPlatform $platform, Section $section): array;

    /**
     * Creates or finds the user; returns their id on the platform.
     */
    public function ensureUser(LmsPlatform $platform, User $user): string;

    public function enroll(LmsPlatform $platform, string $courseId, string $userId, string $role): void;

    public function unenroll(LmsPlatform $platform, string $courseId, string $userId): void;

    /**
     * The users enrolled in the course on the platform: external user id => role.
     *
     * @return array<string, string>
     */
    public function members(LmsPlatform $platform, string $courseId): array;

    /**
     * Final course scores on the platform: external user id => score (0–100 scale as the platform reports it).
     *
     * @return array<string, float>
     */
    public function finalScores(LmsPlatform $platform, string $courseId): array;
}
