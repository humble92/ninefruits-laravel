<?php

namespace App\Policies\Offering;

use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Security\Enums\Permission;
use App\Models\Offering\Section;
use App\Models\User;

/**
 * Sections are managed by staff of the offering unit (sections.manage in scope). Their
 * instructors may write the syllabus.
 */
class SectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::SectionsView->value) || $user->can(Permission::SectionsManage->value);
    }

    public function view(User $user, Section $section): bool
    {
        return Section::query()->whereKey($section->id)->tap(fn ($query) => Section::constrainManageableBy($query, $user))->exists();
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::SectionsManage->value);
    }

    public function update(User $user, Section $section): bool
    {
        return $section->isManageableBy($user);
    }

    public function editSyllabus(User $user, Section $section): bool
    {
        return $section->isTaughtBy($user) || $section->isManageableBy($user);
    }

    /**
     * Only drafts; published sections are cancelled instead (registrations from Phase 5).
     */
    public function delete(User $user, Section $section): bool
    {
        return $section->status === SectionStatus::Draft && $section->isManageableBy($user);
    }
}
