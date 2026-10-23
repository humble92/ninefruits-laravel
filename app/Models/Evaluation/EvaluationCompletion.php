<?php

namespace App\Models\Evaluation;

use App\Models\Registration\Enrollment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * That an enrollment was evaluated, and when. Says nothing about the answers.
 *
 * @property int $id
 * @property int $enrollment_id
 * @property CarbonImmutable $completed_at
 * @property-read Enrollment $enrollment
 */
class EvaluationCompletion extends Model
{
    public $timestamps = false;

    protected $fillable = ['enrollment_id', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<Enrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
