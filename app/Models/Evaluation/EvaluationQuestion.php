<?php

namespace App\Models\Evaluation;

use App\Domain\Evaluation\Enums\QuestionType;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $evaluation_form_id
 * @property int $sort_order
 * @property string|null $category
 * @property string $text
 * @property QuestionType $question_type
 * @property bool $is_required
 * @property bool $is_reversed
 * @property-read EvaluationForm $form
 */
class EvaluationQuestion extends Model implements Translatable
{
    use HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['category', 'text'];

    protected $fillable = ['evaluation_form_id', 'sort_order', 'category', 'text', 'question_type', 'is_required', 'is_reversed'];

    protected $attributes = ['sort_order' => 0, 'category' => null, 'question_type' => 'scale', 'is_required' => true, 'is_reversed' => false];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'question_type' => QuestionType::class, 'is_required' => 'boolean', 'is_reversed' => 'boolean'];
    }

    /**
     * @return BelongsTo<EvaluationForm, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(EvaluationForm::class, 'evaluation_form_id');
    }
}
