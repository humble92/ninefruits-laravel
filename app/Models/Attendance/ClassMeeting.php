<?php

namespace App\Models\Attendance;

use App\Domain\Attendance\Enums\ClassMeetingStatus;
use App\Models\Facility\Room;
use App\Models\Offering\Section;
use App\Models\Offering\SectionMeeting;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One class of a section on a date: generated from the weekly meeting times, or added by
 * hand (make-up class). Cancelled meetings stay and do not count.
 *
 * @property int $id
 * @property int $section_id
 * @property int|null $section_meeting_id
 * @property CarbonImmutable $meeting_date
 * @property string $start_time
 * @property string $end_time
 * @property int|null $room_id
 * @property ClassMeetingStatus $status
 * @property string|null $topic
 * @property string|null $cancel_reason
 * @property-read Section $section
 * @property-read SectionMeeting|null $sectionMeeting
 * @property-read Room|null $room
 * @property-read Collection<int, AttendanceRecord> $records
 */
class ClassMeeting extends Model
{
    protected $fillable = ['section_id', 'section_meeting_id', 'meeting_date', 'start_time', 'end_time', 'room_id', 'status', 'topic', 'cancel_reason'];

    protected $attributes = ['section_meeting_id' => null, 'room_id' => null, 'status' => 'scheduled', 'topic' => null, 'cancel_reason' => null];

    protected function casts(): array
    {
        return ['meeting_date' => 'immutable_date', 'status' => ClassMeetingStatus::class];
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return BelongsTo<SectionMeeting, $this>
     */
    public function sectionMeeting(): BelongsTo
    {
        return $this->belongsTo(SectionMeeting::class);
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return HasMany<AttendanceRecord, $this>
     */
    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * @param  Builder<ClassMeeting>  $query
     */
    public function scopeScheduled(Builder $query): void
    {
        $query->where('status', ClassMeetingStatus::Scheduled);
    }

    public function timeLabel(): string
    {
        return substr($this->start_time, 0, 5).'–'.substr($this->end_time, 0, 5);
    }
}
