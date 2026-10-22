<?php

namespace App\Domain\Admission\Actions;

use App\Domain\Admission\Enums\ApplicationStatus;
use App\Domain\Admission\Notifications\ApplicationSubmittedNotification;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Admission\AdmissionCycleDocumentType;
use App\Models\Admission\Application;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Submits a complete draft while the cycle is open and gives it its application number
 * ({cycle code}-{00001}). After submission the applicant can no longer edit it.
 */
class SubmitApplication
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Application $application, User $applicant): Application
    {
        UpdateApplication::ensureEditableBy($application, $applicant);

        $cycle = $application->cycle;

        if (! $cycle->isAcceptingApplications()) {
            throw ValidationException::withMessages(['status' => __('admissions.errors.cycle_closed')]);
        }

        $this->ensureComplete($application);

        DB::transaction(function () use ($application, $cycle) {
            $number = (int) DB::scalar(<<<'SQL'
                INSERT INTO application_number_sequences (admission_cycle_id, last_value) VALUES (?, 1)
                ON CONFLICT (admission_cycle_id) DO UPDATE SET last_value = application_number_sequences.last_value + 1
                RETURNING last_value
                SQL, [$cycle->id]);

            $application->forceFill([
                'status' => ApplicationStatus::Submitted,
                'application_number' => sprintf('%s-%05d', $cycle->code, $number),
                'submitted_at' => CarbonImmutable::now(),
            ])->save();
        });

        $this->audit->record('application.submitted', $application, null, ['application_number' => $application->application_number], $applicant);
        $applicant->notify(new ApplicationSubmittedNotification($application));

        return $application;
    }

    private function ensureComplete(Application $application): void
    {
        $errors = [];

        if (blank($application->previous_school_name)) {
            $errors['previous_school_name'] = __('admissions.errors.required_field');
        }

        if (blank($application->personal_statement)) {
            $errors['personal_statement'] = __('admissions.errors.required_field');
        }

        $missing = $application->cycle->documentTypes()->with('documentType')->where('is_required', true)->get()
            ->filter(fn (AdmissionCycleDocumentType $type) => AttachApplicationDocument::documentsOfType($application, $type->document_type_id) === []);

        if ($missing->isNotEmpty()) {
            $errors['documents'] = __('admissions.errors.documents_missing', ['documents' => $missing->map(fn (AdmissionCycleDocumentType $type) => $type->documentType->name)->implode(', ')]);
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
