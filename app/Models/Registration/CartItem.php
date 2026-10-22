<?php

namespace App\Models\Registration;

use App\Models\Offering\Section;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A section a student plans to register for. The cart is checked in advance and
 * submitted at once when registration opens (section 10.7).
 *
 * @property int $id
 * @property int $student_record_id
 * @property int $term_id
 * @property int $section_id
 * @property-read Section $section
 */
class CartItem extends Model
{
    protected $fillable = ['student_record_id', 'term_id', 'section_id'];

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
