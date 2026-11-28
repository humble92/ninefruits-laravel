<?php

namespace App\Models\Person;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $basis
 * @property list<array{minimum: numeric-string|int|float}> $thresholds
 * @property bool $is_default
 */
class YearLevelRule extends Model implements Translatable
{
    use HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['name', 'basis', 'thresholds', 'is_default'];

    protected $attributes = ['is_default' => false];

    protected function casts(): array
    {
        return ['thresholds' => 'array', 'is_default' => 'boolean'];
    }
}
