<?php

namespace App\Models\Assessment;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A scored area of a kind (listening, reading, ...), with its maximum and an optional
 * minimum to pass.
 *
 * @property int $id
 * @property int $assessment_type_id
 * @property string $code
 * @property string $name
 * @property string $max_score
 * @property string|null $min_score
 * @property int $sort_order
 * @property-read AssessmentType $type
 */
class AssessmentComponent extends Model implements Translatable
{
    use HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['assessment_type_id', 'code', 'name', 'max_score', 'min_score', 'sort_order'];

    protected $attributes = ['min_score' => null, 'sort_order' => 0];

    protected function casts(): array
    {
        return ['max_score' => 'decimal:1', 'min_score' => 'decimal:1', 'sort_order' => 'integer'];
    }

    /**
     * @return BelongsTo<AssessmentType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(AssessmentType::class, 'assessment_type_id');
    }
}
