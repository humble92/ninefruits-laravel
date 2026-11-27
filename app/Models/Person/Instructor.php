<?php

namespace App\Models\Person;

use App\Domain\Person\Enums\EmploymentType;
use App\Models\Institution\LookupValue;
use App\Models\Organization\OrgUnit;
use Carbon\CarbonImmutable;
use Database\Factories\Person\InstructorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Teaching staff record of a person (legacy FACULTY). One per person.
 *
 * @property int $id
 * @property int $person_id
 * @property string $employee_number
 * @property int $org_unit_id
 * @property EmploymentType $employment_type
 * @property int|null $academic_title_id
 * @property CarbonImmutable|null $hire_date
 * @property CarbonImmutable|null $end_date
 * @property-read Person $person
 * @property-read OrgUnit $orgUnit
 * @property-read LookupValue|null $academicTitle
 */
class Instructor extends Model
{
    /** @use HasFactory<InstructorFactory> */
    use HasFactory;

    public const string ACADEMIC_TITLE_LOOKUP = 'academic_title';

    protected $fillable = ['person_id', 'employee_number', 'org_unit_id', 'employment_type', 'academic_title_id', 'hire_date', 'end_date'];

    protected $attributes = ['academic_title_id' => null, 'hire_date' => null, 'end_date' => null];

    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'hire_date' => 'immutable_date',
            'end_date' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * @return BelongsTo<OrgUnit, $this>
     */
    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    /** @return BelongsTo<LookupValue, $this> */
    public function academicTitle(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class);
    }

    /**
     * @return HasMany<AdvisorAssignment, $this>
     */
    public function advisorAssignments(): HasMany
    {
        return $this->hasMany(AdvisorAssignment::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeCurrent(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', CarbonImmutable::today()));
    }

    /**
     * Employed on the date (today by default).
     */
    public function isCurrent(?CarbonImmutable $on = null): bool
    {
        return $this->end_date === null || $this->end_date->greaterThanOrEqualTo($on ?? CarbonImmutable::today());
    }
}
