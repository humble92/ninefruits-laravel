<?php

namespace App\Domain\Registration\Actions;

use App\Domain\Registration\Enums\HoldType;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Domain\Security\Services\AuthorizationService;
use App\Models\Person\StudentRecord;
use App\Models\Registration\RegistrationHold;
use App\Models\Registration\RegistrationOverride;
use App\Models\Term\Term;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Holds (legacy SRDENY) and overrides (legacy SRALLOW), with reasons, by staff with
 * registration_holds.manage in a scope covering the student's record.
 */
class ManageHolds
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{blocks_registration?: bool, blocks_grades?: bool, blocks_certificates?: bool, starts_at?: CarbonImmutable|null, ends_at?: CarbonImmutable|null, source?: string}  $options
     */
    public function place(StudentRecord $record, HoldType $type, string $reason, User $staff, array $options = []): RegistrationHold
    {
        $this->authorize($record, $staff);

        $hold = RegistrationHold::query()->create([
            'student_record_id' => $record->id,
            'hold_type' => $type,
            'blocks_registration' => $options['blocks_registration'] ?? true,
            'blocks_grades' => $options['blocks_grades'] ?? false,
            'blocks_certificates' => $options['blocks_certificates'] ?? false,
            'starts_at' => $options['starts_at'] ?? CarbonImmutable::now(),
            'ends_at' => $options['ends_at'] ?? null,
            'reason' => $reason,
            'source' => $options['source'] ?? 'manual',
            'created_by_id' => $staff->id,
        ]);
        $this->audit->record('registration_hold.placed', $hold, $reason, ['type' => $type->value], $staff);

        return $hold;
    }

    public function release(RegistrationHold $hold, string $reason, User $staff): RegistrationHold
    {
        $this->authorize($hold->studentRecord, $staff);

        if ($hold->released_at !== null) {
            throw ValidationException::withMessages(['hold' => __('registration.errors.hold_released')]);
        }

        $hold->forceFill(['released_at' => CarbonImmutable::now(), 'released_by_id' => $staff->id, 'release_reason' => $reason])->save();
        $this->audit->record('registration_hold.released', $hold, $reason, causer: $staff);

        return $hold;
    }

    /**
     * @param  array{section_id?: int|null, course_id?: int|null, expires_at?: CarbonImmutable|null}  $limits
     */
    public function grantOverride(StudentRecord $record, Term $term, RuleCode $rule, string $reason, User $staff, array $limits = []): RegistrationOverride
    {
        $this->authorize($record, $staff);

        if ($rule->isCore() && $rule !== RuleCode::Window) {
            throw ValidationException::withMessages(['rule_code' => __('registration.errors.rule_not_overridable')]);
        }

        $override = RegistrationOverride::query()->create([
            'student_record_id' => $record->id,
            'term_id' => $term->id,
            'rule_code' => $rule,
            'section_id' => $limits['section_id'] ?? null,
            'course_id' => $limits['course_id'] ?? null,
            'reason' => $reason,
            'expires_at' => $limits['expires_at'] ?? null,
            'granted_by_id' => $staff->id,
        ]);
        $this->audit->record('registration_override.granted', $override, $reason, ['rule' => $rule->value], $staff);

        return $override;
    }

    public function revokeOverride(RegistrationOverride $override, User $staff): RegistrationOverride
    {
        $this->authorize($override->studentRecord, $staff);
        $override->forceFill(['revoked_at' => CarbonImmutable::now(), 'revoked_by_id' => $staff->id])->save();
        $this->audit->record('registration_override.revoked', $override, causer: $staff);

        return $override;
    }

    private function authorize(StudentRecord $record, User $staff): void
    {
        $scope = app(AuthorizationService::class)->scopeFor($staff, Permission::RegistrationHoldsManage);

        if (! $scope->covers($record->org_unit_id, $record->program_id)) {
            throw new AuthorizationException;
        }
    }
}
