<?php

namespace App\Models\Offering;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use App\Support\Targeting\TargetCriteria;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Seats of a section reserved for a group of students (e.g. 30 for majors), until
 * releases_at when unused seats open to everyone. Enforced by registration (Phase 5).
 *
 * @property int $id
 * @property int $section_id
 * @property string $name
 * @property int $capacity
 * @property TargetCriteria $eligibility
 * @property CarbonImmutable|null $releases_at
 * @property int $sort_order
 * @property-read Section $section
 */
class SectionSeatGroup extends Model implements Translatable
{
    use HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['section_id', 'name', 'capacity', 'eligibility', 'releases_at', 'sort_order'];

    protected $attributes = ['eligibility' => null, 'releases_at' => null, 'sort_order' => 0];

    protected function casts(): array
    {
        return ['capacity' => 'integer', 'eligibility' => TargetCriteria::class, 'releases_at' => 'immutable_datetime', 'sort_order' => 'integer'];
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
