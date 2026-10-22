<?php

namespace App\Models\Admission;

use App\Models\Institution\LookupValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A document applicants upload in a cycle (code list `application_document_type`).
 *
 * @property int $id
 * @property int $admission_cycle_id
 * @property int $document_type_id
 * @property bool $is_required
 * @property-read LookupValue $documentType
 */
class AdmissionCycleDocumentType extends Model
{
    protected $fillable = ['admission_cycle_id', 'document_type_id', 'is_required'];

    protected $attributes = ['is_required' => true];

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }

    /**
     * @return BelongsTo<LookupValue, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'document_type_id');
    }
}
