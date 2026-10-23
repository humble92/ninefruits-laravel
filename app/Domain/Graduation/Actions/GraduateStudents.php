<?php

namespace App\Domain\Graduation\Actions;

use App\Domain\Curriculum\Enums\ProgramRole;
use App\Domain\Graduation\Services\DegreeNumberGenerator;
use App\Domain\Person\Actions\RecordStatusChange;
use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Graduation\DegreeAudit;
use App\Models\Person\StudentRecord;
use App\Models\Person\StudentStatusChangeType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Graduation (legacy: status code Z with no check): the latest stored primary audit must
 * be satisfied; then the graduation status change is recorded with the date and a degree
 * number is issued. One transaction per student, so a batch reports each outcome.
 */
class GraduateStudents
{
    public function __construct(
        private readonly RecordStatusChange $statusChange,
        private readonly DegreeNumberGenerator $numbers,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(StudentRecord $record, CarbonImmutable $graduationDate, User $staff, string $reason): StudentRecord
    {
        if (! $staff->can(Permission::GraduationProcess->value) || ! $record->isVisibleTo($staff)) {
            throw new AuthorizationException;
        }

        if ($record->status !== StudentStatus::Enrolled) {
            throw ValidationException::withMessages(['status' => __('graduation.errors.not_enrolled')]);
        }

        $audit = DegreeAudit::query()->where('student_record_id', $record->id)->where('program_role', ProgramRole::Primary)->orderByDesc('id')->first();

        if ($audit === null || ! $audit->is_satisfied) {
            throw ValidationException::withMessages(['audit' => __($audit === null ? 'graduation.errors.no_audit' : 'graduation.errors.audit_not_satisfied')]);
        }

        $type = StudentStatusChangeType::query()->where('code', 'graduation')->sole();

        return DB::transaction(function () use ($record, $graduationDate, $staff, $reason, $type, $audit) {
            $this->statusChange->handle($record, $type, $graduationDate, $reason, $staff);
            $record->loadMissing('program');
            $record->forceFill(['degree_number' => $this->numbers->next($record->program, $graduationDate)])->save();
            $this->audit->record('graduation.processed', $record, $reason, [
                'student_number' => $record->student_number, 'graduation_date' => $graduationDate->toDateString(), 'degree_number' => $record->degree_number, 'audit_id' => $audit->id,
            ], $staff);

            return $record;
        });
    }

    /**
     * @param  iterable<int, StudentRecord>  $records
     * @return array{graduated: int, errors: list<string>}
     */
    public function batch(iterable $records, CarbonImmutable $graduationDate, User $staff, string $reason): array
    {
        $summary = ['graduated' => 0, 'errors' => []];

        foreach ($records as $record) {
            try {
                $this->handle($record, $graduationDate, $staff, $reason);
                $summary['graduated']++;
            } catch (ValidationException $e) {
                $summary['errors'][] = $record->student_number.': '.implode(' ', array_merge(...array_values($e->errors())));
            }
        }

        return $summary;
    }
}
