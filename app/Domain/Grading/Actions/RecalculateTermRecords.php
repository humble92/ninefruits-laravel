<?php

namespace App\Domain\Grading\Actions;

use App\Domain\Grading\Services\TermRecordCalculator;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Recalculates term records from finalized grades, e.g. after a grading policy change.
 * With a term: students with grades in it, from that term on. Standing and honors stay as
 * decided at term close.
 */
class RecalculateTermRecords
{
    public function __construct(
        private readonly TermRecordCalculator $calculator,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return int students recalculated
     */
    public function handle(?Term $term, ?StudentRecord $only, string $reason, ?User $staff): int
    {
        if ($staff !== null && ! $staff->can(Permission::GradesManage->value)) {
            throw new AuthorizationException;
        }

        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => __('registration.errors.reason_required')]);
        }

        $records = StudentRecord::query()
            ->when($only !== null, fn ($query) => $query->whereKey($only?->id))
            ->when($term !== null, fn ($query) => $query->whereIn('id', Enrollment::query()->where('term_id', $term?->id)->select('student_record_id')))
            ->whereIn('id', Enrollment::query()->whereNotNull('grade_finalized_at')->select('student_record_id'));
        $count = 0;

        foreach ($records->lazyById() as $record) {
            $this->calculator->recalculate($record, $term);
            $count++;
        }

        $this->audit->record('grades.recalculated', $term ?? $only, $reason, ['term' => $term?->code, 'student' => $only?->student_number, 'students' => $count], $staff);

        return $count;
    }
}
