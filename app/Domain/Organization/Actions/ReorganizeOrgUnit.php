<?php

namespace App\Domain\Organization\Actions;

use App\Domain\Organization\Enums\OrgUnitChangeType;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\OrgUnitSuccession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records a reorganization: the unit ends the day before the effective date and its
 * successors are linked. History is never rewritten; past records keep pointing at the
 * old unit, and the succession tells which unit took over (porting/porting-plan.md 10.4).
 */
class ReorganizeOrgUnit
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  list<OrgUnit>  $successors
     */
    public function handle(
        OrgUnit $unit,
        OrgUnitChangeType $changeType,
        array $successors,
        CarbonImmutable $effectiveDate,
        string $reason,
        ?User $changedBy = null,
    ): void {
        if ($successors === []) {
            throw ValidationException::withMessages(['successors' => __('reference.errors.successor_required')]);
        }

        if ($changeType !== OrgUnitChangeType::Split && count($successors) > 1) {
            throw ValidationException::withMessages(['successors' => __('reference.errors.single_successor')]);
        }

        if ($effectiveDate->lessThanOrEqualTo($unit->effective_start_date)) {
            throw ValidationException::withMessages(['effective_date' => __('reference.errors.reorganization_before_start')]);
        }

        foreach ($successors as $successor) {
            if ($successor->is($unit)) {
                throw ValidationException::withMessages(['successors' => __('reference.errors.successor_is_self')]);
            }
        }

        DB::transaction(function () use ($unit, $changeType, $successors, $effectiveDate, $reason, $changedBy) {
            $lastDay = $effectiveDate->subDay();

            if ($unit->effective_end_date === null || $unit->effective_end_date->greaterThan($lastDay)) {
                $unit->update(['effective_end_date' => $lastDay]);
            }

            foreach ($successors as $successor) {
                OrgUnitSuccession::query()->updateOrCreate(
                    ['predecessor_org_unit_id' => $unit->id, 'successor_org_unit_id' => $successor->id],
                    ['change_type' => $changeType, 'effective_date' => $effectiveDate, 'reason' => $reason],
                );
            }

            $this->audit->record('org_unit.reorganized', $unit, $reason, [
                'change_type' => $changeType->value,
                'successor_ids' => array_map(fn (OrgUnit $successor) => $successor->id, $successors),
                'effective_date' => $effectiveDate->toDateString(),
            ], $changedBy);
        });
    }
}
