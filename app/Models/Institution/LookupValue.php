<?php

namespace App\Models\Institution;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Database\Factories\Institution\LookupValueFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $lookup_type_id
 * @property string $code
 * @property string $name
 * @property int $sort_order
 * @property bool $is_active
 * @property-read LookupType $type
 */
class LookupValue extends Model implements Translatable
{
    /** @use HasFactory<LookupValueFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['lookup_type_id', 'code', 'name', 'sort_order', 'is_active'];

    protected $attributes = ['is_active' => true, 'sort_order' => 0];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * @return BelongsTo<LookupType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(LookupType::class, 'lookup_type_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOfType(Builder $query, string $typeCode): void
    {
        $query->whereHas('type', fn (Builder $type) => $type->where('code', $typeCode));
    }
}
