<?php

namespace App\Domain\Admission\Actions;

use App\Domain\Admission\Enums\ApplicationStatus;
use App\Domain\Person\Actions\RegisterStudent;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Admission\Application;
use App\Models\Person\StudentRecord;
use App\Models\Term\Term;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turns a confirmed applicant into a student: a new student record with a new student
 * number in the admission term (docs/porting-plan.md section 10.1). The person and the
 * account stay the same, so the applicant signs in as a student with the same login. A
 * graduate applying again keeps the earlier record.
 *
 * Admissions staff may not manage student records, so the record is created as a system
 * actor after checking applications.matriculate (the audit still names the staff member).
 */
class MatriculateApplicant
{
    public function __construct(
        private readonly RegisterStudent $registerStudent,
        private readonly DatabaseSessionContext $context,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(Application $application, Term $admissionTerm, User $matriculatedBy): StudentRecord
    {
        if (! $application->isInScopeOf($matriculatedBy, Permission::ApplicationsMatriculate)) {
            throw new AuthorizationException;
        }

        self::ensureReady($application);

        return $this->context->asSystem('matriculation', fn () => DB::transaction(function () use ($application, $admissionTerm, $matriculatedBy) {
            $record = $this->registerStudent->handle($application->person, $application->program, [
                'admission_date' => $admissionTerm->start_date,
                'admission_term_id' => $admissionTerm->id,
                'admission_type_id' => $application->admission_type_id,
            ], $matriculatedBy);

            $application->forceFill(['student_record_id' => $record->id, 'matriculated_at' => CarbonImmutable::now()])->save();

            $this->audit->record('application.matriculated', $application, null, [
                'student_record_id' => $record->id,
                'student_number' => $record->student_number,
                'term' => $admissionTerm->code,
            ], $matriculatedBy);

            return $record;
        }));
    }

    public static function ensureReady(Application $application): void
    {
        if ($application->status !== ApplicationStatus::Confirmed || $application->student_record_id !== null) {
            throw ValidationException::withMessages(['status' => __('admissions.errors.not_ready_to_matriculate')]);
        }
    }
}
