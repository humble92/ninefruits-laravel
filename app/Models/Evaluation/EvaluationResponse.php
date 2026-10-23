<?php

namespace App\Models\Evaluation;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * One anonymous response for a section: no student, no sequence, no time (random UUID).
 *
 * @property string $id
 * @property int $term_id
 * @property int $section_id
 * @property int $evaluation_form_id
 * @property-read Collection<int, EvaluationAnswer> $answers
 */
class EvaluationResponse extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['term_id', 'section_id', 'evaluation_form_id'];

    /**
     * Random (v4) ids: ordered ids would follow the completion order.
     */
    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    /**
     * @return HasMany<EvaluationAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(EvaluationAnswer::class);
    }
}
