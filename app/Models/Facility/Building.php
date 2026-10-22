<?php

namespace App\Models\Facility;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Database\Factories\Facility\BuildingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $address
 * @property bool $is_active
 */
class Building extends Model implements Translatable
{
    /** @use HasFactory<BuildingFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['code', 'name', 'address', 'is_active'];

    protected $attributes = ['address' => null, 'is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @return HasMany<Room, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
