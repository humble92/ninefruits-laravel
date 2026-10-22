<?php

namespace App\Domain\Admission\Actions;

use App\Domain\Security\Services\AuditLogger;
use App\Models\Admission\AdmissionCycle;
use App\Models\Admission\Application;
use App\Models\Organization\Program;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates a draft application of the signed-in person. One open application per cycle;
 * graduates and current students apply with the same account.
 */
class StartApplication
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(User $applicant, AdmissionCycle $cycle, Program $program): Application
    {
        if ($applicant->person_id === null) {
            throw ValidationException::withMessages(['admission_cycle_id' => __('admissions.errors.no_person')]);
        }

        if (! $cycle->isAcceptingApplications()) {
            throw ValidationException::withMessages(['admission_cycle_id' => __('admissions.errors.cycle_closed')]);
        }

        self::ensureOffered($cycle, $program);

        try {
            // Own savepoint: on PostgreSQL a failed statement would abort the caller's transaction.
            $application = DB::transaction(fn () => Application::query()->create([
                'admission_cycle_id' => $cycle->id,
                'person_id' => $applicant->person_id,
                'program_id' => $program->id,
                'org_unit_id' => $program->org_unit_id,
            ]));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['admission_cycle_id' => __('admissions.errors.already_applied')]);
        }

        $this->audit->record('application.started', $application, null, ['cycle' => $cycle->code, 'program' => $program->code], $applicant);

        return $application;
    }

    public static function ensureOffered(AdmissionCycle $cycle, Program $program): void
    {
        if (! $cycle->programs()->where('program_id', $program->id)->exists()) {
            throw ValidationException::withMessages(['program_id' => __('admissions.errors.program_not_offered')]);
        }
    }
}
