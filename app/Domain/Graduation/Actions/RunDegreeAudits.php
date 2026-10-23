<?php

namespace App\Domain\Graduation\Actions;

use App\Domain\Graduation\Data\DegreeAuditResult;
use App\Domain\Graduation\Services\DegreeAudit;
use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Graduation\DegreeAudit as StoredAudit;
use App\Models\Person\StudentRecord;
use App\Models\Term\Term;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Stores audits: for one student (the screen's "save audit") or for every current student
 * of a term's cohort (the command). Each run adds a snapshot; the newest is the one
 * graduation reads.
 */
class RunDegreeAudits
{
    public function __construct(private readonly DegreeAudit $audit, private readonly AuditLogger $log) {}

    /**
     * @return list<StoredAudit>
     */
    public function forRecord(StudentRecord $record, ?User $staff, ?Term $term = null): array
    {
        if ($staff !== null && (! $staff->can(Permission::GraduationAudit->value) || ! $record->isVisibleTo($staff))) {
            throw new AuthorizationException;
        }

        $stored = [];

        DB::transaction(function () use ($record, $staff, $term, &$stored) {
            foreach ($this->audit->evaluateAll($record) as $result) {
                $stored[] = $this->store($record, $result, $staff, $term);
            }
        });

        return $stored;
    }

    /**
     * Every enrolled student (or those with the given minimum year level); returns counts.
     *
     * @return array{students: int, satisfied: int}
     */
    public function forAll(?Term $term, ?User $staff, ?int $minimumYearLevel = null, ?string $studentNumber = null): array
    {
        $summary = ['students' => 0, 'satisfied' => 0];
        $query = StudentRecord::query()->where('status', StudentStatus::Enrolled)
            ->when($minimumYearLevel !== null, fn (Builder $q) => $q->where('year_level', '>=', $minimumYearLevel))
            ->when($studentNumber !== null, fn (Builder $q) => $q->where('student_number', $studentNumber))
            ->with('program');

        foreach ($query->cursor() as $record) {
            $primary = $this->forRecord($record, null, $term)[0];
            $summary['students']++;
            $summary['satisfied'] += $primary->is_satisfied ? 1 : 0;
        }

        if ($staff !== null) {
            $this->log->record('graduation.audits_run', $term, null, $summary, $staff);
        }

        return $summary;
    }

    private function store(StudentRecord $record, DegreeAuditResult $result, ?User $staff, ?Term $term): StoredAudit
    {
        return StoredAudit::query()->create([
            'student_record_id' => $record->id, 'curriculum_requirement_set_id' => $result->set?->id, 'program_role' => $result->role, 'program_id' => $result->program->id,
            'term_id' => $term?->id, 'is_satisfied' => $result->isSatisfied(), 'earned_credits' => $result->earnedCredits, 'required_credits' => $result->requiredCredits,
            'cumulative_gpa' => $result->cumulativeGpa, 'result' => $result->toArray(), 'audited_at' => CarbonImmutable::now(), 'audited_by_id' => $staff?->id,
        ]);
    }
}
