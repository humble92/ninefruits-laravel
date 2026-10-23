<?php

namespace App\Models\Graduation;

use App\Domain\Graduation\Enums\WaiverStatus;
use App\Models\Curriculum\CurriculumRequirement;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A requirement waived for one student (transfer credit, change of major): requested with
 * a reason, approved by someone else, and possibly lifted later. The audit counts a
 * waiver only while it is approved.
 *
 * @property int $id
 * @property int $student_record_id
 * @property int $curriculum_requirement_id
 * @property string $reason
 * @property WaiverStatus $status
 * @property int|null $requested_by_id
 * @property CarbonImmutable $requested_at
 * @property int|null $decided_by_id
 * @property CarbonImmutable|null $decided_at
 * @property string|null $decision_note
 * @property int|null $lifted_by_id
 * @property CarbonImmutable|null $lifted_at
 * @property string|null $lift_reason
 * @property-read StudentRecord $studentRecord
 * @property-read CurriculumRequirement $requirement
 * @property-read User|null $requestedBy
 * @property-read User|null $decidedBy
 */
class DegreeRequirementWaiver extends Model
{
    protected $fillable = [
        'student_record_id', 'curriculum_requirement_id', 'reason', 'status', 'requested_by_id', 'requested_at', 'decided_by_id', 'decided_at', 'decision_note',
        'lifted_by_id', 'lifted_at', 'lift_reason',
    ];

    protected $attributes = ['status' => 'pending', 'requested_by_id' => null, 'decided_by_id' => null, 'decided_at' => null, 'decision_note' => null, 'lifted_by_id' => null, 'lifted_at' => null, 'lift_reason' => null];

    protected function casts(): array
    {
        return ['status' => WaiverStatus::class, 'requested_at' => 'immutable_datetime', 'decided_at' => 'immutable_datetime', 'lifted_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<StudentRecord, $this>
     */
    public function studentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentRecord::class);
    }

    /**
     * @return BelongsTo<CurriculumRequirement, $this>
     */
    public function requirement(): BelongsTo
    {
        return $this->belongsTo(CurriculumRequirement::class, 'curriculum_requirement_id');
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
     * @param  Builder<DegreeRequirementWaiver>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', WaiverStatus::Approved);
    }
}
