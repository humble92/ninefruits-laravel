<?php

namespace App\Models\Certificate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The wording of a certificate kind in one language: title, body paragraphs with
 * {placeholders}, footer and signer.
 *
 * @property int $id
 * @property int $certificate_type_id
 * @property string $locale
 * @property string $title
 * @property string $body
 * @property string|null $footer
 * @property string|null $signer
 * @property-read CertificateType $type
 */
class CertificateTemplate extends Model
{
    protected $fillable = ['certificate_type_id', 'locale', 'title', 'body', 'footer', 'signer'];

    protected $attributes = ['footer' => null, 'signer' => null];

    /**
     * @return BelongsTo<CertificateType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(CertificateType::class, 'certificate_type_id');
    }
}
