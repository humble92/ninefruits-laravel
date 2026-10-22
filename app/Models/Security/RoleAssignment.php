<?php

namespace App\Models\Security;

use App\Domain\Security\Enums\ScopeType;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\Program;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A role granted to a user within a scope (global, org unit, program) for a period.
 *
 * @property int $id
 * @property int $user_id
 * @property int $role_id
 * @property ScopeType $scope_type
 * @property int|null $scope_id
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property string|null $reason
 * @property int|null $assigned_by_id
 * @property-read User $user
 * @property-read Role $role
 */
class RoleAssignment extends Model
{
    protected $fillable = ['user_id', 'role_id', 'scope_type', 'scope_id', 'starts_at', 'ends_at', 'reason', 'assigned_by_id'];

    protected function casts(): array
    {
        return [
            'scope_type' => ScopeType::class,
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query, ?CarbonImmutable $at = null): void
    {
        self::constrainToActive($query, $at);
    }

    /**
     * Assignments in effect at the given time (default now). Also usable where the query
     * is not typed as a RoleAssignment builder, such as relationship count constraints.
     *
     * @param  Builder<self>  $query
     */
    public static function constrainToActive(Builder $query, ?CarbonImmutable $at = null): void
    {
        $at ??= CarbonImmutable::now();

        $query->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $at))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $at));
    }

    /**
     * "All", or the scope type with the org unit / program code and name.
     */
    public function describeScope(): string
    {
        $target = match ($this->scope_type) {
            ScopeType::Global => null,
            ScopeType::OrgUnit => OrgUnit::query()->find($this->scope_id),
            ScopeType::Program => Program::query()->find($this->scope_id),
        };

        if ($this->scope_type === ScopeType::Global) {
            return $this->scope_type->label();
        }

        return $this->scope_type->label().': '.($target === null ? '#'.$this->scope_id : $target->getAttribute('code').' '.$target->getAttribute('name'));
    }

    public function isActive(?CarbonImmutable $at = null): bool
    {
        $at ??= CarbonImmutable::now();

        return ($this->starts_at === null || $this->starts_at->lessThanOrEqualTo($at))
            && ($this->ends_at === null || $this->ends_at->greaterThan($at));
    }
}
