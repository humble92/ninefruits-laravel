<?php

namespace App\Models\Organization;

use App\Domain\Organization\Enums\DegreeLevel;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuthorizationService;
use App\Models\Concerns\HasEffectivePeriod;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\Organization\ProgramFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Degree or study program offered by an org unit.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $degree_title
 * @property DegreeLevel $degree_level
 * @property int $org_unit_id
 * @property string|null $standard_duration_years
 * @property CarbonImmutable $effective_start_date
 * @property CarbonImmutable|null $effective_end_date
 * @property bool $allows_second_major
 * @property bool $allows_minor
 * @property-read OrgUnit $orgUnit
 */
class Program extends Model implements Translatable
{
    /** @use HasFactory<ProgramFactory> */
    use HasEffectivePeriod, HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name', 'degree_title'];

    protected $fillable = ['code', 'name', 'degree_title', 'degree_level', 'org_unit_id', 'standard_duration_years', 'effective_start_date', 'effective_end_date', 'allows_second_major', 'allows_minor'];

    protected $attributes = ['degree_title' => null, 'standard_duration_years' => null, 'effective_end_date' => null, 'allows_second_major' => false, 'allows_minor' => false];

    protected function casts(): array
    {
        return [
            'degree_level' => DegreeLevel::class,
            'standard_duration_years' => 'decimal:1',
            'effective_start_date' => 'immutable_date',
            'effective_end_date' => 'immutable_date',
            'allows_second_major' => 'boolean',
            'allows_minor' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<OrgUnit, $this>
     */
    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    /**
     * Whether the user decides second major and minor matters of this program
     * (additional_programs.approve in its scope).
     */
    public function isAdditionalProgramDecidableBy(User $user): bool
    {
        return app(AuthorizationService::class)->scopeFor($user, Permission::AdditionalProgramsApprove)->covers($this->org_unit_id, $this->id);
    }
}
