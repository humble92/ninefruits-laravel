<?php

namespace App\Models\Assessment;

use App\Domain\Assessment\Enums\ResultSource;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A student's result for a kind: from one of our sessions (with the area scores) or from
 * an outside body (with the evidence file).
 *
 * @property int $id
 * @property int $student_record_id
 * @property int $assessment_type_id
 * @property int|null $assessment_session_id
 * @property int|null $assessment_registration_id
 * @property ResultSource $source
 * @property CarbonImmutable $taken_date
 * @property string|null $total_score
 * @property string|null $level
 * @property bool $is_passed
 * @property string|null $certificate_number
 * @property string|null $note
 * @property int|null $entered_by_id
 * @property CarbonImmutable $entered_at
 * @property-read StudentRecord $studentRecord
 * @property-read AssessmentType $type
 * @property-read AssessmentSession|null $session
 * @property-read AssessmentRegistration|null $registration
 * @property-read User|null $enteredBy
 * @property-read Collection<int, AssessmentComponentScore> $componentScores
 */
class AssessmentResult extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const string EVIDENCE_COLLECTION = 'evidence';

    protected $fillable = [
        'student_record_id', 'assessment_type_id', 'assessment_session_id', 'assessment_registration_id', 'source', 'taken_date', 'total_score', 'level', 'is_passed',
        'certificate_number', 'note', 'entered_by_id', 'entered_at',
    ];

    protected $attributes = ['assessment_session_id' => null, 'assessment_registration_id' => null, 'total_score' => null, 'level' => null, 'certificate_number' => null, 'note' => null, 'entered_by_id' => null];

    protected function casts(): array
    {
        return ['source' => ResultSource::class, 'taken_date' => 'immutable_date', 'total_score' => 'decimal:1', 'is_passed' => 'boolean', 'entered_at' => 'immutable_datetime'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::EVIDENCE_COLLECTION)
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png'])
            ->useDisk((string) config('media-library.disk_name'));
    }

    /**
     * @return BelongsTo<StudentRecord, $this>
     */
    public function studentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentRecord::class);
    }

    /**
     * @return BelongsTo<AssessmentType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(AssessmentType::class, 'assessment_type_id');
    }

    /**
     * @return BelongsTo<AssessmentSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AssessmentSession::class, 'assessment_session_id');
    }

    /**
     * @return BelongsTo<AssessmentRegistration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(AssessmentRegistration::class, 'assessment_registration_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by_id');
    }

    /**
     * @return HasMany<AssessmentComponentScore, $this>
     */
    public function componentScores(): HasMany
    {
        return $this->hasMany(AssessmentComponentScore::class);
    }
}
