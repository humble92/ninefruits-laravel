<?php

namespace App\Models\Curriculum;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Database\Factories\Curriculum\CourseCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * E.g. general education required, major elective (legacy SFOBJDIST).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int|null $parent_id
 * @property int $sort_order
 * @property bool $is_active
 * @property-read CourseCategory|null $parent
 */
class CourseCategory extends Model implements Translatable
{
    /** @use HasFactory<CourseCategoryFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['code', 'name', 'parent_id', 'sort_order', 'is_active'];

    protected $attributes = ['parent_id' => null, 'sort_order' => 0, 'is_active' => true];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_active' => 'boolean'];
    }

    /**
     * @return BelongsTo<CourseCategory, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
