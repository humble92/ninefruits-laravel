<?php

namespace App\Models\Attendance;

use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Models\Registration\Enrollment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student's attendance at one class meeting.
 *
 * @property int $id
 * @property int $class_meeting_id
 * @property int $enrollment_id
 * @property AttendanceStatus $status
 * @property string|null $note
 * @property int|null $recorded_by_id
 * @property CarbonImmutable $recorded_at
 * @property-read ClassMeeting $meeting
 * @property-read Enrollment $enrollment
 * @property-read User|null $recordedBy
 */
class AttendanceRecord extends Model
{
    protected $fillable = ['class_meeting_id', 'enrollment_id', 'status', 'note', 'recorded_by_id', 'recorded_at'];

    protected $attributes = ['note' => null, 'recorded_by_id' => null];

    protected function casts(): array
    {
        return ['status' => AttendanceStatus::class, 'recorded_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<ClassMeeting, $this>
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(ClassMeeting::class, 'class_meeting_id');
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
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }
}
