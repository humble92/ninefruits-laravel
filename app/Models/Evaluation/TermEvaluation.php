<?php

namespace App\Models\Evaluation;

use App\Models\Term\Term;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A term's course evaluation: the form, the minimum responses for results to show, and when
 * the results were published to instructors. The evaluation period is the course
 * evaluation window of each section's session.
 *
 * @property int $id
 * @property int $term_id
 * @property int $evaluation_form_id
 * @property int $minimum_responses
 * @property int $minimum_response_percent
 * @property CarbonImmutable|null $results_published_at
 * @property int|null $published_by_id
 * @property-read Term $term
 * @property-read EvaluationForm $form
 */
class TermEvaluation extends Model
{
    protected $fillable = ['term_id', 'evaluation_form_id', 'minimum_responses', 'minimum_response_percent'];

    protected $attributes = ['minimum_responses' => 5, 'minimum_response_percent' => 0, 'results_published_at' => null, 'published_by_id' => null];

    protected function casts(): array
    {
        return ['minimum_responses' => 'integer', 'minimum_response_percent' => 'integer', 'results_published_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * @return BelongsTo<EvaluationForm, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(EvaluationForm::class, 'evaluation_form_id');
    }
}
