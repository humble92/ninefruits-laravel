<?php

namespace App\Models\Assessment;

use App\Domain\Assessment\Enums\CorrectionStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A requested change to a published result: the proposed values, the reason, and the
 * decision by someone else.
 *
 * @property int $id
 * @property int $assessment_result_id
 * @property array{scores?: array<string, float|int|string>, total?: float|null, passed?: bool|null, note?: string|null} $proposed
 * @property array{scores?: array<string, float|int|string>, total?: float|null, passed?: bool|null, note?: string|null} $previous
 * @property string $reason
 * @property CorrectionStatus $status
 * @property int $requested_by_id
 * @property CarbonImmutable $requested_at
 * @property int|null $decided_by_id
 * @property CarbonImmutable|null $decided_at
 * @property string|null $decision_note
 * @property-read AssessmentResult $result
 * @property-read User $requestedBy
 * @property-read User|null $decidedBy
 */
class AssessmentResultCorrection extends Model
{
    protected $fillable = ['assessment_result_id', 'proposed', 'previous', 'reason', 'status', 'requested_by_id', 'requested_at', 'decided_by_id', 'decided_at', 'decision_note'];

    protected $attributes = ['status' => 'pending', 'decided_by_id' => null, 'decided_at' => null, 'decision_note' => null];

    protected function casts(): array
    {
        return ['proposed' => 'array', 'previous' => 'array', 'status' => CorrectionStatus::class, 'requested_at' => 'immutable_datetime', 'decided_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<AssessmentResult, $this>
     */
    public function result(): BelongsTo
    {
        return $this->belongsTo(AssessmentResult::class, 'assessment_result_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_id');
    }

    /**
     * @param  Builder<AssessmentResultCorrection>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', CorrectionStatus::Pending);
    }
}
