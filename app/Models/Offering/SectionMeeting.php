<?php

namespace App\Models\Offering;

use App\Models\Facility\Room;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A weekly meeting of a section: weekday, times, room and dates (normally the session's).
 * A room and an instructor cannot be in two places at once (database constraint and
 * triggers).
 *
 * @property int $id
 * @property int $section_id
 * @property int $weekday ISO-8601, 1 = Monday
 * @property string $start_time HH:MM:SS
 * @property string $end_time HH:MM:SS
 * @property int|null $room_id
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property bool $is_active
 * @property-read Section $section
 * @property-read Room|null $room
 */
class SectionMeeting extends Model
{
    protected $fillable = ['section_id', 'weekday', 'start_time', 'end_time', 'room_id', 'start_date', 'end_date'];

    protected $attributes = ['room_id' => null, 'is_active' => true];

    protected function casts(): array
    {
        return ['weekday' => 'integer', 'start_date' => 'immutable_date', 'end_date' => 'immutable_date', 'is_active' => 'boolean'];
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * "09:00–10:15".
     */
    public function timeLabel(): string
    {
        return substr($this->start_time, 0, 5).'–'.substr($this->end_time, 0, 5);
    }

    public function startMinute(): int
    {
        return self::minutes($this->start_time);
    }

    public function endMinute(): int
    {
        return self::minutes($this->end_time);
    }

    public static function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $hours * 60 + $minutes;
    }
}
