<?php

namespace App\Policies\Certificate;

use App\Domain\Security\Enums\Permission;
use App\Models\Certificate\CertificateIssue;
use App\Models\Person\StudentRecord;
use App\Models\User;

/**
 * The ledger: issued through IssueCertificate, never edited or deleted (revoked instead).
 */
class CertificateIssuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::CertificatesIssue->value) || $user->can(Permission::CertificatesManage->value);
    }

    public function view(User $user, CertificateIssue $issue): bool
    {
        // The holder. Checked by query: under RLS the record relation is empty for anyone else.
        return $this->viewAny($user)
            || ($user->person_id !== null && StudentRecord::query()->whereKey($issue->student_record_id)->where('person_id', $user->person_id)->exists());
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CertificateIssue $issue): bool
    {
        return false;
    }

    public function delete(User $user, CertificateIssue $issue): bool
    {
        return false;
    }
}
