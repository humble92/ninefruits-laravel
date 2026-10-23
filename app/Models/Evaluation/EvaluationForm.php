<?php

namespace App\Models\Evaluation;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A course evaluation questionnaire (legacy SURCATEGORY + SURCONTENT, loaded by hand-run SQL
 * each term). A term uses one form; a form in use with responses keeps its questions.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property int $scale_points
 * @property string|null $scale_low_label
 * @property string|null $scale_high_label
 * @property bool $is_active
 * @property-read Collection<int, EvaluationQuestion> $questions
 */
class EvaluationForm extends Model implements Translatable
{
    use HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name', 'description', 'scale_low_label', 'scale_high_label'];

    protected $fillable = ['code', 'name', 'description', 'scale_points', 'scale_low_label', 'scale_high_label', 'is_active'];

    protected $attributes = ['description' => null, 'scale_points' => 5, 'scale_low_label' => null, 'scale_high_label' => null, 'is_active' => true];

    protected function casts(): array
    {
        return ['scale_points' => 'integer', 'is_active' => 'boolean'];
    }

    /**
     * @return HasMany<EvaluationQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(EvaluationQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Questions change only while no term's responses use them.
     */
    public function isLocked(): bool
    {
        return EvaluationResponse::query()->where('evaluation_form_id', $this->id)->exists();
    }
}
