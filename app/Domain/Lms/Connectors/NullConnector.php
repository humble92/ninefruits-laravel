<?php

namespace App\Domain\Lms\Connectors;

use App\Domain\Lms\Contracts\LmsConnector;
use App\Models\Lms\LmsPlatform;
use App\Models\Offering\Section;
use App\Models\User;

/**
 * Talks to nobody: returns deterministic identifiers so links, logs, events and the
 * reconcile loop can be exercised before a real platform is connected. Its member list is
 * what the academic system last pushed (kept in the platform options for the demo).
 */
class NullConnector implements LmsConnector
{
    public function ensureCourse(LmsPlatform $platform, Section $section): array
    {
        $base = rtrim((string) ($platform->base_url ?? 'https://lms.example.test'), '/');

        return ['id' => 'course-'.$section->id, 'url' => "{$base}/course/view.php?id={$section->id}"];
    }

    public function ensureUser(LmsPlatform $platform, User $user): string
    {
        return 'user-'.$user->id;
    }

    public function enroll(LmsPlatform $platform, string $courseId, string $userId, string $role): void
    {
        // Nothing to call.
    }

    public function unenroll(LmsPlatform $platform, string $courseId, string $userId): void
    {
        // Nothing to call.
    }

    public function members(LmsPlatform $platform, string $courseId): array
    {
        return [];
    }

    public function finalScores(LmsPlatform $platform, string $courseId): array
    {
        return [];
    }
}
