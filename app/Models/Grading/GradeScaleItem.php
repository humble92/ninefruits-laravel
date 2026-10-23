<?php

namespace App\Models\Grading;

use App\Domain\Grading\Enums\GradeAppliesTo;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Database\Factories\Grading\GradeScaleItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $grade_scale_id
 * @property string $code
 * @property string|null $name
 * @property string|null $grade_points
 * @property string|null $min_percentage
 * @property string|null $max_percentage
 * @property bool $counts_in_gpa
 * @property bool $is_passing
 * @property GradeAppliesTo $applies_to
 * @property int $sort_order
 * @property-read GradeScale $scale
 */
class GradeScaleItem extends Model implements Translatable
{
    /** @use HasFactory<GradeScaleItemFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['grade_scale_id', 'code', 'name', 'grade_points', 'min_percentage', 'max_percentage', 'counts_in_gpa', 'is_passing', 'applies_to', 'sort_order'];

    protected $attributes = ['name' => null, 'grade_points' => null, 'min_percentage' => null, 'max_percentage' => null, 'counts_in_gpa' => true, 'is_passing' => true, 'applies_to' => 'graded', 'sort_order' => 0];

    protected function casts(): array
    {
        return [
            'grade_points' => 'decimal:2',
            'min_percentage' => 'decimal:2',
            'max_percentage' => 'decimal:2',
            'counts_in_gpa' => 'boolean',
            'is_passing' => 'boolean',
            'applies_to' => GradeAppliesTo::class,
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<GradeScale, $this>
     */
    public function scale(): BelongsTo
    {
        return $this->belongsTo(GradeScale::class, 'grade_scale_id');
    }
}
