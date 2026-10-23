<?php

namespace App\Models\Certificate;

use App\Domain\Certificate\Enums\CertificateAudience;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A kind of certificate (enrollment, transcript, graduation, ...), with a template per
 * language the institution edits.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property CertificateAudience $audience
 * @property bool $has_grades
 * @property bool $has_photo
 * @property bool $is_active
 * @property int $sort_order
 * @property-read Collection<int, CertificateTemplate> $templates
 */
class CertificateType extends Model implements Translatable
{
    use HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['code', 'name', 'audience', 'has_grades', 'has_photo', 'is_active', 'sort_order'];

    protected $attributes = ['audience' => 'any', 'has_grades' => false, 'has_photo' => false, 'is_active' => true, 'sort_order' => 0];

    protected function casts(): array
    {
        return ['audience' => CertificateAudience::class, 'has_grades' => 'boolean', 'has_photo' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * @return HasMany<CertificateTemplate, $this>
     */
    public function templates(): HasMany
    {
        return $this->hasMany(CertificateTemplate::class);
    }
}
