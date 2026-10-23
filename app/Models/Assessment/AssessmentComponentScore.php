<?php

namespace App\Models\Assessment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The score of one area of a result.
 *
 * @property int $id
 * @property int $assessment_result_id
 * @property int $assessment_component_id
 * @property string $score
 * @property-read AssessmentResult $result
 * @property-read AssessmentComponent $component
 */
class AssessmentComponentScore extends Model
{
    protected $fillable = ['assessment_result_id', 'assessment_component_id', 'score'];

    protected function casts(): array
    {
        return ['score' => 'decimal:1'];
    }

    /**
     * @return BelongsTo<AssessmentResult, $this>
     */
    public function result(): BelongsTo
    {
        return $this->belongsTo(AssessmentResult::class, 'assessment_result_id');
    }

    /**
     * @return BelongsTo<AssessmentComponent, $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(AssessmentComponent::class, 'assessment_component_id');
    }
}
