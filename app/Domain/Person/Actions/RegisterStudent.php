<?php

namespace App\Domain\Person\Actions;

use App\Domain\Person\Services\StudentNumberGenerator;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Organization\Program;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates a student record (one admission) for a person, with a new student number unless
 * one is given. A person who studied before keeps their person row and account; only the
 * record is new (docs/porting-plan.md section 10.1). Phase 3 matriculation uses this.
 */
class RegisterStudent
{
    public function __construct(
        private readonly StudentNumberGenerator $numbers,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{admission_date: string|CarbonImmutable, catalog_year?: int|null, year_level?: int|null, admission_term_id?: int|null, admission_type_id?: int|null, institutional_email?: string|null, student_number?: string|null, tags?: list<string>|null}  $details
     */
    public function handle(Person $person, Program $program, array $details, ?User $registeredBy = null): StudentRecord
    {
        if ($person->merged_into_person_id !== null) {
            throw ValidationException::withMessages(['person_id' => __('people.errors.person_merged')]);
        }

        $admissionDate = CarbonImmutable::parse($details['admission_date']);

        if (! $program->isEffectiveOn($admissionDate)) {
            throw ValidationException::withMessages(['program_id' => __('people.errors.program_not_effective')]);
        }

        return DB::transaction(function () use ($person, $program, $details, $admissionDate, $registeredBy) {
            $number = filled($details['student_number'] ?? null) ? (string) $details['student_number'] : $this->numbers->next($admissionDate->year);

            if ((bool) DB::scalar('SELECT util.student_number_exists(?)', [$number])) {
                throw ValidationException::withMessages(['student_number' => __('people.errors.student_number_taken')]);
            }

            $record = new StudentRecord([
                'person_id' => $person->id,
                'student_number' => $number,
                'program_id' => $program->id,
                'org_unit_id' => $program->org_unit_id,
                'admission_date' => $admissionDate,
                'catalog_year' => $details['catalog_year'] ?? $admissionDate->year,
                'year_level' => $details['year_level'] ?? 1,
                'admission_term_id' => $details['admission_term_id'] ?? null,
                'admission_type_id' => $details['admission_type_id'] ?? null,
                'institutional_email' => $details['institutional_email'] ?? null,
                'tags' => $details['tags'] ?? null,
            ]);
            $record->forceFill(['status_changed_at' => CarbonImmutable::now()])->save();

            $this->audit->record('student_record.registered', $record, null, [
                'person_id' => $person->id,
                'student_number' => $number,
                'program' => $program->code,
            ], $registeredBy);

            return $record;
        });
    }
}
