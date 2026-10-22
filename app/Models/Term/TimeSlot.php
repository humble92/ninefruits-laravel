<?php

namespace App\Models\Term;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Database\Factories\Term\TimeSlotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class period (legacy TTTIME), e.g. period 1 from 09:00 to 09:50 on weekdays.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $start_time
 * @property string $end_time
 * @property list<int> $weekdays ISO-8601 weekday numbers, 1 = Monday
 * @property int $sort_order
 * @property bool $is_active
 */
class TimeSlot extends Model implements Translatable
{
    /** @use HasFactory<TimeSlotFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['code', 'name', 'start_time', 'end_time', 'weekdays', 'sort_order', 'is_active'];

    protected $attributes = ['sort_order' => 0, 'is_active' => true];

    protected function casts(): array
    {
        return ['weekdays' => 'array', 'sort_order' => 'integer', 'is_active' => 'boolean'];
    }
}
