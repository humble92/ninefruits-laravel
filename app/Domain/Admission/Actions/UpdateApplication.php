<?php

namespace App\Domain\Admission\Actions;

use App\Domain\Admission\Enums\ApplicationStatus;
use App\Models\Admission\Application;
use App\Models\Organization\Program;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/**
 * The applicant edits a draft. Changes are recorded by the data audit trigger; drafts are
 * not business decisions, so no audit entry with a reason.
 */
class UpdateApplication
{
    /**
     * @param  array<string, mixed>  $data  Keys from Application::APPLICANT_FIELDS
     */
    public function handle(Application $application, array $data, User $applicant): Application
    {
        self::ensureEditableBy($application, $applicant);

        $data = Arr::only($data, Application::APPLICANT_FIELDS);

        if (isset($data['program_id']) && (int) $data['program_id'] !== $application->program_id) {
            $program = Program::query()->findOrFail((int) $data['program_id']);
            StartApplication::ensureOffered($application->cycle, $program);
            $application->org_unit_id = $program->org_unit_id;
        }

        $application->fill($data)->save();

        return $application;
    }

    public static function ensureEditableBy(Application $application, User $applicant): void
    {
        if (! $application->isOwnedBy($applicant)) {
            throw new AuthorizationException;
        }

        if ($application->status !== ApplicationStatus::Draft) {
            throw ValidationException::withMessages(['status' => __('admissions.errors.not_draft')]);
        }
    }
}
