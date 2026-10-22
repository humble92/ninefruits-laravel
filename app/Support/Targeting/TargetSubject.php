<?php

namespace App\Support\Targeting;

/**
 * Something that target criteria are checked against, usually a student record
 * (Phase 2). Implementations return org units including all ancestors, so a criterion on a
 * college matches a student of any of its departments.
 */
interface TargetSubject
{
    /** @return list<int> */
    public function targetProgramIds(): array;

    /** @return list<int> Org units of the subject and all their ancestors */
    public function targetOrgUnitIds(): array;

    public function targetAdmissionYear(): ?int;

    public function targetYearLevel(): ?int;

    public function targetStudentStatus(): ?string;

    public function targetAcademicStanding(): ?string;

    /** @return list<string> */
    public function targetTags(): array;
}
