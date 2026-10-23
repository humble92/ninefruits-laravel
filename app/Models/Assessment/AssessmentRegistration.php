<?php

namespace App\Models\Assessment;

use App\Domain\Assessment\Enums\AssessmentRegistrationStatus;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A student's place in a session (legacy CET.APPLYCHECK). Cancelled rows stay.
 *
 * @property int $id
 * @property int $assessment_session_id
 * @property int $student_record_id
 * @property AssessmentRegistrationStatus $status
 * @property CarbonImmutable $registered_at
 * @property int|null $registered_by_id
 * @property CarbonImmutable|null $cancelled_at
 * @property string|null $cancel_reason
 * @property-read AssessmentSession $session
 * @property-read StudentRecord $studentRecord
 * @property-read User|null $registeredBy
 * @property-read AssessmentResult|null $result
 */
class AssessmentRegistration extends Model
{
    protected $fillable = ['assessment_session_id', 'student_record_id', 'status', 'registered_at', 'registered_by_id', 'cancelled_at', 'cancel_reason'];

    protected $attributes = ['status' => 'registered', 'registered_by_id' => null, 'cancelled_at' => null, 'cancel_reason' => null];

    protected function casts(): array
    {
        return ['status' => AssessmentRegistrationStatus::class, 'registered_at' => 'immutable_datetime', 'cancelled_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<AssessmentSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AssessmentSession::class, 'assessment_session_id');
    }

    /**
     * @return BelongsTo<StudentRecord, $this>
     */
    public function studentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentRecord::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_id');
    }

    /**
     * @return HasOne<AssessmentResult, $this>
     */
    public function result(): HasOne
    {
        return $this->hasOne(AssessmentResult::class);
    }

    /**
     * @param  Builder<AssessmentRegistration>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', [AssessmentRegistrationStatus::Registered, AssessmentRegistrationStatus::Absent]);
    }
}
