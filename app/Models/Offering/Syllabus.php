<?php

namespace App\Models\Offering;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The syllabus of a section, written by its instructors in one language. Content keys:
 * overview, objectives, assessment (list of {item, weight}), materials, weekly_plan (list
 * of {week, topic}), policies. Students see it once published.
 *
 * @property int $id
 * @property int $section_id
 * @property string $language
 * @property array{overview?: string|null, objectives?: string|null, assessment?: list<array{item: string, weight: int|float|null}>, materials?: string|null, weekly_plan?: list<array{week: int, topic: string}>, policies?: string|null} $content
 * @property CarbonImmutable|null $published_at
 * @property int|null $updated_by_id
 * @property CarbonImmutable $updated_at
 * @property-read Section $section
 * @property-read User|null $updatedBy
 */
class Syllabus extends Model
{
    protected $table = 'syllabi';

    protected $fillable = ['section_id', 'language', 'content'];

    protected $attributes = ['published_at' => null, 'updated_by_id' => null];

    protected function casts(): array
    {
        return ['content' => 'array', 'published_at' => 'immutable_datetime'];
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
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
