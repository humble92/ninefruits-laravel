<?php

namespace App\Domain\Person\Actions;

use App\Domain\Admission\Enums\ApplicationStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Admission\Application;
use App\Models\Person\AccountInvitation;
use App\Models\Person\Guardian;
use App\Models\Person\Instructor;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\Security\UserIdentity;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Merges a duplicate person into another: student records, applications, guardians, the
 * instructor record and the account move to the target; the duplicate stays as a pointer
 * (merged_into_person_id) so old references resolve. When both have an account, the
 * duplicate's account is deactivated and its SSO identities move where possible.
 */
class MergePeople
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly DatabaseSessionContext $context,
    ) {}

    public function handle(Person $duplicate, Person $target, string $reason, User $mergedBy): Person
    {
        if (! $mergedBy->can(Permission::PeopleMerge->value) || ! $mergedBy->can(Permission::PeopleManage->value)) {
            throw new AuthorizationException;
        }

        if ($duplicate->is($target) || $duplicate->merged_into_person_id !== null || $target->merged_into_person_id !== null) {
            throw ValidationException::withMessages(['target_id' => __('people.errors.merge_invalid')]);
        }

        if ($duplicate->instructor()->exists() && $target->instructor()->exists()) {
            throw ValidationException::withMessages(['target_id' => __('people.errors.merge_two_instructors')]);
        }

        // Both cannot keep an open application in the same cycle (one open per person and cycle).
        $openCycles = fn (Person $person) => Application::query()->where('person_id', $person->id)
            ->whereIn('status', ApplicationStatus::openValues())->pluck('admission_cycle_id')->all();

        if (array_intersect($openCycles($duplicate), $openCycles($target)) !== []) {
            throw ValidationException::withMessages(['target_id' => __('people.errors.merge_two_open_applications')]);
        }

        return DB::transaction(function () use ($duplicate, $target, $reason, $mergedBy) {
            $moved = [
                'student_records' => StudentRecord::query()->where('person_id', $duplicate->id)->update(['person_id' => $target->id]),
                'guardians' => Guardian::query()->where('person_id', $duplicate->id)->update(['person_id' => $target->id]),
                'instructor' => Instructor::query()->where('person_id', $duplicate->id)->update(['person_id' => $target->id]),
                // Staff who merge people may not manage applications; moving them is part of
                // the merge, which was authorized above.
                'applications' => $this->context->asSystem('person_merge', fn () => Application::query()->where('person_id', $duplicate->id)->update(['person_id' => $target->id])),
                'invitations' => AccountInvitation::query()->where('person_id', $duplicate->id)->whereNull('accepted_at')->delete(),
            ];

            foreach (['email', 'phone', 'address', 'postal_code', 'birth_date', 'gender', 'nationality_country_id', 'ethnicity_id'] as $field) {
                if ($target->getAttribute($field) === null && $duplicate->getAttribute($field) !== null) {
                    $target->setAttribute($field, $duplicate->getAttribute($field));
                }
            }

            foreach (['national_id', 'passport_number'] as $field) {
                if ($target->getAttribute("{$field}_encrypted") === null && $duplicate->getAttribute("{$field}_encrypted") !== null) {
                    $target->setAttribute("{$field}_encrypted", $duplicate->getAttribute("{$field}_encrypted"));
                }
            }

            $target->setAttribute('name_variants', array_merge($duplicate->name_variants ?? [], $target->name_variants ?? []) ?: null);
            $target->save();

            $moved['account'] = $this->moveAccount($duplicate, $target);

            $duplicate->forceFill(['merged_into_person_id' => $target->id])->save();

            $this->audit->record('person.merged', $target, $reason, ['duplicate_person_id' => $duplicate->id, 'moved' => $moved], $mergedBy);

            return $target;
        });
    }

    private function moveAccount(Person $duplicate, Person $target): string
    {
        $duplicateUser = User::query()->where('person_id', $duplicate->id)->first();

        if ($duplicateUser === null) {
            return 'none';
        }

        $targetUser = User::query()->where('person_id', $target->id)->first();

        if ($targetUser === null) {
            $duplicateUser->forceFill(['person_id' => $target->id])->save();

            return 'moved';
        }

        foreach ($duplicateUser->identities()->get() as $identity) {
            /** @var UserIdentity $identity */
            if (! $targetUser->identities()->where('provider', $identity->provider)->exists()) {
                $identity->update(['user_id' => $targetUser->id]);
            }
        }

        $duplicateUser->forceFill(['person_id' => null, 'is_active' => false])->save();

        return 'deactivated';
    }
}
