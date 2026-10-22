<?php

namespace App\Models\Institution;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Database\Factories\Institution\LookupTypeFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A list of institution specific codes, e.g. ethnicity, admission type, room type.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property bool $is_system
 * @property-read Collection<int, LookupValue> $values
 */
class LookupType extends Model implements Translatable
{
    /** @use HasFactory<LookupTypeFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    protected $fillable = ['code', 'name', 'description', 'is_system'];

    protected $attributes = ['is_system' => false, 'description' => null];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    /**
     * @return HasMany<LookupValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(LookupValue::class)->orderBy('sort_order')->orderBy('code');
    }
}
