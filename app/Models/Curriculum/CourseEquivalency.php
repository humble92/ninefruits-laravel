<?php

namespace App\Models\Curriculum;

use App\Models\Concerns\HasEffectivePeriod;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A substitution between courses for a period, e.g. after a curriculum reform the new
 * course counts for the old one (and, if mutual, the other way round).
 *
 * @property int $id
 * @property int $course_id
 * @property int $equivalent_course_id
 * @property bool $is_mutual
 * @property CarbonImmutable $effective_start_date
 * @property CarbonImmutable|null $effective_end_date
 * @property string|null $note
 * @property-read Course $course
 * @property-read Course $equivalentCourse
 */
class CourseEquivalency extends Model implements Translatable
{
    use HasEffectivePeriod, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['note'];

    protected $fillable = ['course_id', 'equivalent_course_id', 'is_mutual', 'effective_start_date', 'effective_end_date', 'note'];

    protected $attributes = ['is_mutual' => true, 'effective_end_date' => null, 'note' => null];

    protected function casts(): array
    {
        return ['is_mutual' => 'boolean', 'effective_start_date' => 'immutable_date', 'effective_end_date' => 'immutable_date'];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function equivalentCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'equivalent_course_id');
    }
}
