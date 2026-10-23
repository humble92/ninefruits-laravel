<?php

namespace App\Models\Grading;

use App\Domain\Grading\Enums\EvaluationMethod;
use App\Domain\Grading\Enums\RequestStatus;
use App\Models\Offering\Section;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An instructor's request to evaluate a section relatively (or absolutely again), decided
 * by the staff who schedule the section (decided 2026-10-23).
 *
 * @property int $id
 * @property int $section_id
 * @property EvaluationMethod $requested_method
 * @property string $reason
 * @property RequestStatus $status
 * @property int $requested_by_id
 * @property CarbonImmutable $requested_at
 * @property int|null $decided_by_id
 * @property CarbonImmutable|null $decided_at
 * @property string|null $decision_note
 * @property-read Section $section
 * @property-read User $requestedBy
 * @property-read User|null $decidedBy
 */
class EvaluationMethodRequest extends Model
{
    protected $fillable = ['section_id', 'requested_method', 'reason', 'requested_by_id', 'requested_at'];

    protected $attributes = ['status' => 'pending', 'decided_by_id' => null, 'decided_at' => null, 'decision_note' => null];

    protected function casts(): array
    {
        return [
            'requested_method' => EvaluationMethod::class,
            'status' => RequestStatus::class,
            'requested_at' => 'immutable_datetime',
            'decided_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
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
     * @param  Builder<self>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', RequestStatus::Pending);
    }
}
