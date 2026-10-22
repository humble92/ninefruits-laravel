<?php

namespace App\Domain\Person\Actions;

use App\Domain\Person\Enums\AdditionalProgramRequestStatus;
use App\Domain\Person\Enums\AdditionalProgramRole;
use App\Domain\Person\Settings\StudentRecordSettings;
use App\Domain\Security\Services\AuditLogger;
use App\Domain\Term\Enums\TermWindowType;
use App\Domain\Term\Services\TermResolver;
use App\Models\Organization\Program;
use App\Models\Person\AdditionalProgramRequest;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A student applies for a second major or minor during an open application window (term
 * window "additional program application", which may be limited by year level etc.). The
 * program must accept the role, and the student stays within the institution's limits.
 */
class RequestAdditionalProgram
{
    public function __construct(
        private readonly TermResolver $terms,
        private readonly StudentRecordSettings $settings,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(StudentRecord $record, Program $program, AdditionalProgramRole $role, ?string $statement, User $student): AdditionalProgramRequest
    {
        if ($record->person_id !== $student->person_id) {
            throw ValidationException::withMessages(['student_record_id' => __('people.errors.not_own_record')]);
        }

        if (! $record->status->isActive()) {
            throw ValidationException::withMessages(['student_record_id' => __('people.errors.additional_program_inactive_record')]);
        }

        $term = $this->terms->termsOpenFor(TermWindowType::AdditionalProgramApplication, $record)->first()
            ?? throw ValidationException::withMessages(['program_id' => __('people.errors.additional_program_window_closed')]);

        self::ensureAllowed($record, $program, $role, $this->settings);

        try {
            // Own savepoint: on PostgreSQL a failed statement would abort the caller's transaction.
            $request = DB::transaction(fn () => AdditionalProgramRequest::query()->create([
                'student_record_id' => $record->id,
                'program_id' => $program->id,
                'org_unit_id' => $program->org_unit_id,
                'role' => $role,
                'term_id' => $term->id,
                'statement' => $statement,
                'requested_by_id' => $student->id,
                'requested_at' => CarbonImmutable::now(),
            ]));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['program_id' => __('people.errors.additional_program_pending')]);
        }

        $this->audit->record('additional_program.requested', $request, $statement, ['program' => $program->code, 'role' => $role->value], $student);

        return $request;
    }

    /**
     * Checked again when staff approve, since things may have changed.
     */
    public static function ensureAllowed(StudentRecord $record, Program $program, AdditionalProgramRole $role, StudentRecordSettings $settings, ?int $ignoreRequestId = null): void
    {
        $accepts = $role === AdditionalProgramRole::SecondMajor ? $program->allows_second_major : $program->allows_minor;

        if (! $accepts || ! $program->isEffectiveOn()) {
            throw ValidationException::withMessages(['program_id' => __('people.errors.additional_program_not_offered')]);
        }

        if ($program->id === $record->program_id || $record->additionalPrograms()->where('program_id', $program->id)->whereNull('end_date')->exists()) {
            throw ValidationException::withMessages(['program_id' => __('people.errors.additional_program_already_held')]);
        }

        $held = $record->additionalPrograms()->where('role', $role)->whereNull('end_date')->count();
        $pending = AdditionalProgramRequest::query()->where('student_record_id', $record->id)->where('role', $role)
            ->where('status', AdditionalProgramRequestStatus::Requested)
            ->when($ignoreRequestId !== null, fn ($query) => $query->whereKeyNot($ignoreRequestId))
            ->count();
        $limit = $role === AdditionalProgramRole::SecondMajor ? $settings->max_second_majors : $settings->max_minors;

        if ($held + $pending >= $limit) {
            throw ValidationException::withMessages(['role' => __('people.errors.additional_program_limit', ['count' => $limit])]);
        }
    }
}
