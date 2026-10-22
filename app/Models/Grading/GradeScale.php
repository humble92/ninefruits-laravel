<?php

namespace App\Models\Grading;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Database\Factories\Grading\GradeScaleFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Letter grades and grade points (legacy SSCOREDEF), e.g. a 4.5 scale.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $max_grade_points
 * @property bool $is_default
 * @property-read Collection<int, GradeScaleItem> $items
 */
class GradeScale extends Model implements Translatable
{
    /** @use HasFactory<GradeScaleFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['code', 'name', 'max_grade_points', 'is_default'];

    protected $attributes = ['is_default' => false];

    protected function casts(): array
    {
        return ['max_grade_points' => 'decimal:2', 'is_default' => 'boolean'];
    }

    /**
     * @return HasMany<GradeScaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(GradeScaleItem::class)->orderBy('sort_order');
    }
}
