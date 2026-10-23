<?php

namespace App\Models\Assessment;

use App\Domain\Assessment\Enums\AssessmentCategory;
use App\Domain\Assessment\Enums\ScoringMethod;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A kind of examination (legacy CET_TYPE, the SSCORE_ADD exam numbers and COMEXAMTYPE):
 * its areas, how it is scored and what passes.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property AssessmentCategory $category
 * @property ScoringMethod $scoring
 * @property string|null $max_score
 * @property string|null $pass_score
 * @property bool $allows_external
 * @property bool $is_active
 * @property int $sort_order
 * @property-read Collection<int, AssessmentComponent> $components
 * @property-read Collection<int, AssessmentSession> $sessions
 */
class AssessmentType extends Model implements Translatable
{
    use HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['code', 'name', 'category', 'scoring', 'max_score', 'pass_score', 'allows_external', 'is_active', 'sort_order'];

    protected $attributes = ['category' => 'comprehensive', 'scoring' => 'score', 'max_score' => null, 'pass_score' => null, 'allows_external' => false, 'is_active' => true, 'sort_order' => 0];

    protected function casts(): array
    {
        return [
            'category' => AssessmentCategory::class, 'scoring' => ScoringMethod::class, 'max_score' => 'decimal:1', 'pass_score' => 'decimal:1',
            'allows_external' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<AssessmentComponent, $this>
     */
    public function components(): HasMany
    {
        return $this->hasMany(AssessmentComponent::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<AssessmentSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(AssessmentSession::class);
    }
}
