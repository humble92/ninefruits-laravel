<?php

namespace App\Models\Certificate;

use App\Domain\Certificate\Enums\CertificateIssueStatus;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * An issued certificate: the ledger row, the stored PDF and the verification code. Never
 * deleted; revoked with a reason instead.
 *
 * @property int $id
 * @property int $student_record_id
 * @property int $certificate_type_id
 * @property string $locale
 * @property string $serial_number
 * @property string $verification_code
 * @property string|null $purpose
 * @property int $copies
 * @property CertificateIssueStatus $status
 * @property CarbonImmutable $issued_at
 * @property int|null $issued_by_id
 * @property string|null $pdf_checksum
 * @property CarbonImmutable|null $revoked_at
 * @property int|null $revoked_by_id
 * @property string|null $revocation_reason
 * @property-read StudentRecord $studentRecord
 * @property-read CertificateType $type
 * @property-read User|null $issuedBy
 */
class CertificateIssue extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const string PDF_COLLECTION = 'pdf';

    protected $fillable = ['student_record_id', 'certificate_type_id', 'locale', 'serial_number', 'verification_code', 'purpose', 'copies', 'issued_at', 'issued_by_id'];

    protected $attributes = ['purpose' => null, 'copies' => 1, 'status' => 'issued', 'issued_by_id' => null, 'pdf_checksum' => null, 'revoked_at' => null, 'revoked_by_id' => null, 'revocation_reason' => null];

    protected function casts(): array
    {
        return ['copies' => 'integer', 'status' => CertificateIssueStatus::class, 'issued_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PDF_COLLECTION)->singleFile()->acceptsMimeTypes(['application/pdf'])->useDisk((string) config('media-library.disk_name'));
    }

    /**
     * @return BelongsTo<StudentRecord, $this>
     */
    public function studentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentRecord::class);
    }

    /**
     * @return BelongsTo<CertificateType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(CertificateType::class, 'certificate_type_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_id');
    }
}
