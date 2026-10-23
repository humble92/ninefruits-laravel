<?php

namespace App\Models\Evaluation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $evaluation_response_id
 * @property int $evaluation_question_id
 * @property int|null $value
 * @property string|null $comment
 * @property-read EvaluationQuestion $question
 */
class EvaluationAnswer extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'evaluation_response_id';

    protected $fillable = ['evaluation_response_id', 'evaluation_question_id', 'value', 'comment'];

    protected $attributes = ['value' => null, 'comment' => null];

    protected function casts(): array
    {
        return ['value' => 'integer'];
    }

    /**
     * @return BelongsTo<EvaluationQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(EvaluationQuestion::class, 'evaluation_question_id');
    }
}
