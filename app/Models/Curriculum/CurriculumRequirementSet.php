<?php

namespace App\Models\Curriculum;

use App\Domain\Curriculum\Enums\ProgramRole;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use App\Models\Organization\Program;
use Database\Factories\Curriculum\CurriculumRequirementSetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Graduation requirements of a program in a role (primary, second major, minor) for a
 * range of catalog years. Ranges of one program and role never overlap, so a student's
 * catalog year names exactly one set.
 *
 * @property int $id
 * @property int $program_id
 * @property ProgramRole $program_role
 * @property int $catalog_year_from
 * @property int|null $catalog_year_to
 * @property string $name
 * @property string|null $total_credits
 * @property string|null $note
 * @property-read Program $program
 * @property-read Collection<int, CurriculumRequirement> $requirements
 */
class CurriculumRequirementSet extends Model implements Translatable
{
    /** @use HasFactory<CurriculumRequirementSetFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name', 'note'];

    protected $fillable = ['program_id', 'program_role', 'catalog_year_from', 'catalog_year_to', 'name', 'total_credits', 'note'];

    protected $attributes = ['catalog_year_to' => null, 'total_credits' => null, 'note' => null];

    protected function casts(): array
    {
        return ['program_role' => ProgramRole::class, 'catalog_year_from' => 'integer', 'catalog_year_to' => 'integer', 'total_credits' => 'decimal:1'];
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return HasMany<CurriculumRequirement, $this>
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(CurriculumRequirement::class)->orderBy('sort_order');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForCatalogYear(Builder $query, int $catalogYear): void
    {
        $query->whereRaw('catalog_years @> ?::int', [$catalogYear]);
    }

    /**
     * The set that applies to a program, role and catalog year.
     */
    public static function applicable(int $programId, ProgramRole $role, int $catalogYear): ?self
    {
        return self::query()->where('program_id', $programId)->where('program_role', $role)->forCatalogYear($catalogYear)->first();
    }
}
