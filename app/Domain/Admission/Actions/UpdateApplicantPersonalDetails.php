<?php

namespace App\Domain\Admission\Actions;

use App\Domain\Person\Enums\Gender;
use App\Models\Person\Person;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Applicants who have never been a student or instructor fill in their own name, birth
 * date and other details on the application. Once a person has a student or instructor
 * record these are kept by the registrar and only contact details remain self editable.
 */
class UpdateApplicantPersonalDetails
{
    public const array FIELDS = ['family_name', 'given_name', 'birth_date', 'gender', 'nationality_country_id', 'email', 'phone', 'address', 'postal_code'];

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $applicant, array $input): Person
    {
        $person = Person::query()->findOrFail($applicant->person_id);

        if (! self::canEditIdentity($person)) {
            throw ValidationException::withMessages(['family_name' => __('admissions.errors.identity_locked')]);
        }

        $data = Validator::make(Arr::only($input, self::FIELDS), [
            'family_name' => ['required', 'string', 'max:255'],
            'given_name' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'nationality_country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'string', 'max:16'],
        ])->validate();

        $person->fill(array_map(fn (mixed $value) => $value === '' ? null : $value, $data))->save();

        return $person;
    }

    public static function canEditIdentity(Person $person): bool
    {
        return ! $person->studentRecords()->exists() && ! $person->instructor()->exists();
    }
}
