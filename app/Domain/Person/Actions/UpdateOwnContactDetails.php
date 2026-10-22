<?php

namespace App\Domain\Person\Actions;

use App\Models\Person\Person;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

/**
 * A person changes their own contact details in the portal. Only Person::SELF_EDITABLE
 * fields; everything else is changed by staff (and audited by the trigger either way).
 */
class UpdateOwnContactDetails
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $user, array $input): Person
    {
        $person = Person::query()->findOrFail($user->person_id);

        $data = Validator::make(Arr::only($input, Person::SELF_EDITABLE), [
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'string', 'max:16'],
        ])->validate();

        $person->fill($data)->save();

        return $person;
    }
}
