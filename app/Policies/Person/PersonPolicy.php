<?php

namespace App\Policies\Person;

use App\Domain\Security\Enums\Permission;
use App\Models\Person\Person;
use App\Models\User;

class PersonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::PeopleView->value)
            || $user->can(Permission::StudentRecordsView->value)
            || $user->can(Permission::StudentRecordsManage->value);
    }

    public function view(User $user, Person $person): bool
    {
        return $person->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::PeopleManage->value) || $user->can(Permission::StudentRecordsManage->value);
    }

    public function update(User $user, Person $person): bool
    {
        if ($user->can(Permission::PeopleManage->value) || $person->created_by_id === $user->id) {
            return true;
        }

        return $person->studentRecords()->get()->contains(fn ($record) => $record->isManageableBy($user));
    }

    public function viewSensitive(User $user, Person $person): bool
    {
        return $user->can(Permission::PeopleViewSensitive->value) && $this->view($user, $person);
    }

    public function merge(User $user, Person $person): bool
    {
        return $user->can(Permission::PeopleMerge->value) && $user->can(Permission::PeopleManage->value) && $person->merged_into_person_id === null;
    }

    public function invite(User $user, Person $person): bool
    {
        return $user->can(Permission::AccountsInvite->value) && $this->view($user, $person);
    }

    public function delete(User $user, Person $person): bool
    {
        return false;
    }
}
