<?php

namespace App\Models\Grading;

use App\Domain\Grading\Enums\GradeChangeStatus;
use App\Models\Registration\Enrollment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A change to a finalized grade (legacy 교무처 edits with SSCORE_EDIT_BACKUP, which had no
 * reason or approval). Someone other than the requester approves; approval applies it.
 *
 * @property int $id
 * @property int $enrollment_id
 * @property string|null $from_grade_code
 * @property string $to_grade_code
 * @property string|null $to_score
 * @property string $reason
 * @property GradeChangeStatus $status
 * @property int $requested_by_id
 * @property CarbonImmutable $requested_at
 * @property int|null $decided_by_id
 * @property CarbonImmutable|null $decided_at
 * @property string|null $decision_note
 * @property CarbonImmutable|null $applied_at
 * @property-read Enrollment $enrollment
 * @property-read User $requestedBy
 * @property-read User|null $decidedBy
 */
class GradeChangeRequest extends Model
{
    protected $fillable = ['enrollment_id', 'from_grade_code', 'to_grade_code', 'to_score', 'reason', 'requested_by_id', 'requested_at'];

    protected $attributes = [
        'from_grade_code' => null, 'to_score' => null, 'status' => 'pending', 'decided_by_id' => null, 'decided_at' => null,
        'decision_note' => null, 'applied_at' => null,
    ];

    protected function casts(): array
    {
        return [
            'to_score' => 'decimal:2',
            'status' => GradeChangeStatus::class,
            'requested_at' => 'immutable_datetime',
            'decided_at' => 'immutable_datetime',
            'applied_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Enrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
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
        $query->where('status', GradeChangeStatus::Pending);
    }
}
