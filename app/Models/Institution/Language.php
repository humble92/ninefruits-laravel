<?php

namespace App\Models\Institution;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Database\Factories\Institution\LanguageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ISO 639-1 language.
 *
 * @property int $id
 * @property string $code
 * @property string|null $alpha3_code
 * @property string $name
 * @property bool $is_active
 */
class Language extends Model implements Translatable
{
    /** @use HasFactory<LanguageFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['code', 'alpha3_code', 'name', 'is_active'];

    protected $attributes = ['is_active' => true, 'alpha3_code' => null];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
