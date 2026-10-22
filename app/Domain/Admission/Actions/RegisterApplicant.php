<?php

namespace App\Domain\Admission\Actions;

use App\Actions\Fortify\PasswordValidationRules;
use App\Domain\Admission\Settings\AdmissionSettings;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Person\Person;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Self registration of applicants (Fortify registration): a person and an account with the
 * personal email, which must be verified before applying. The account is kept for life
 * (applicant, student, graduate). Runs as a system actor because the visitor has no
 * account yet and row level security would not let them create a person.
 */
class RegisterApplicant implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        private readonly AdmissionSettings $settings,
        private readonly DatabaseSessionContext $context,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        if (! $this->settings->applicant_registration_enabled) {
            throw ValidationException::withMessages(['email' => __('admissions.errors.registration_closed')]);
        }

        /** @var array{family_name: string, given_name: string|null, email: string, password: string} $data */
        $data = Validator::make($input, [
            'family_name' => ['required', 'string', 'max:255'],
            'given_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
        ])->validate();

        return $this->context->asSystem('registration', fn () => DB::transaction(function () use ($data) {
            $person = Person::query()->create([
                'family_name' => $data['family_name'],
                'given_name' => $data['given_name'] ?? null,
                'email' => $data['email'],
            ]);

            $user = new User;
            $user->forceFill([
                'person_id' => $person->id,
                'name' => null,
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'locale' => app()->getLocale(),
            ])->save();

            $this->audit->record('account.registered', $user, null, ['person_id' => $person->id], $user, 'security');

            return $user;
        }));
    }
}
