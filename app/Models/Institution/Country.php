<?php

namespace App\Models\Institution;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Database\Factories\Institution\CountryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ISO 3166-1 country.
 *
 * @property int $id
 * @property string $code
 * @property string $alpha3_code
 * @property string|null $numeric_code
 * @property string $name
 * @property bool $is_active
 */
class Country extends Model implements Translatable
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['code', 'alpha3_code', 'numeric_code', 'name', 'is_active'];

    protected $attributes = ['is_active' => true, 'numeric_code' => null];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
