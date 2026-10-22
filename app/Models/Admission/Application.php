<?php

namespace App\Models\Admission;

use App\Domain\Admission\Enums\ApplicationStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuthorizationService;
use App\Models\Institution\Country;
use App\Models\Institution\LookupValue;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\Program;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\Admission\ApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * One application of a person to a program in an admission cycle. Uploaded documents are
 * media in the `documents` collection with the custom property `document_type_id`.
 *
 * @property int $id
 * @property string|null $application_number
 * @property int $admission_cycle_id
 * @property int $person_id
 * @property int $program_id
 * @property int $org_unit_id
 * @property int|null $admission_type_id
 * @property ApplicationStatus $status
 * @property string|null $previous_school_name
 * @property int|null $previous_school_country_id
 * @property CarbonImmutable|null $previous_graduation_date
 * @property string|null $personal_statement
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $review_started_at
 * @property int|null $decided_by_id
 * @property CarbonImmutable|null $decided_at
 * @property string|null $decision_note
 * @property CarbonImmutable|null $decision_released_at
 * @property CarbonImmutable|null $offer_expires_at
 * @property CarbonImmutable|null $responded_at
 * @property CarbonImmutable|null $withdrawn_at
 * @property string|null $withdrawal_reason
 * @property int|null $student_record_id
 * @property CarbonImmutable|null $matriculated_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read AdmissionCycle $cycle
 * @property-read Person $person
 * @property-read Program $program
 * @property-read OrgUnit $orgUnit
 * @property-read LookupValue|null $admissionType
 * @property-read Country|null $previousSchoolCountry
 * @property-read User|null $decidedBy
 * @property-read StudentRecord|null $studentRecord
 */
class Application extends Model implements HasMedia
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory, InteractsWithMedia;

    public const string DOCUMENTS_COLLECTION = 'documents';

    /** Code list of document types a cycle can ask for. */
    public const string DOCUMENT_TYPE_LOOKUP = 'application_document_type';

    /** Code list of admission types (also on student records). */
    public const string ADMISSION_TYPE_LOOKUP = 'admission_type';

    /** Fields the applicant fills in on the form. */
    public const array APPLICANT_FIELDS = ['program_id', 'admission_type_id', 'previous_school_name', 'previous_school_country_id', 'previous_graduation_date', 'personal_statement'];

    protected $fillable = ['admission_cycle_id', 'person_id', 'program_id', 'org_unit_id', ...self::APPLICANT_FIELDS];

    protected $attributes = [
        'application_number' => null, 'admission_type_id' => null, 'status' => 'draft', 'previous_school_name' => null,
        'previous_school_country_id' => null, 'previous_graduation_date' => null, 'personal_statement' => null,
        'submitted_at' => null, 'review_started_at' => null, 'decided_by_id' => null, 'decided_at' => null, 'decision_note' => null,
        'decision_released_at' => null, 'offer_expires_at' => null, 'responded_at' => null, 'withdrawn_at' => null,
        'withdrawal_reason' => null, 'student_record_id' => null, 'matriculated_at' => null,
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'previous_graduation_date' => 'immutable_date',
            'submitted_at' => 'immutable_datetime',
            'review_started_at' => 'immutable_datetime',
            'decided_at' => 'immutable_datetime',
            'decision_released_at' => 'immutable_datetime',
            'offer_expires_at' => 'immutable_datetime',
            'responded_at' => 'immutable_datetime',
            'withdrawn_at' => 'immutable_datetime',
            'matriculated_at' => 'immutable_datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::DOCUMENTS_COLLECTION)
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png'])
            ->useDisk((string) config('media-library.disk_name'));
    }

    /**
     * @return BelongsTo<AdmissionCycle, $this>
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(AdmissionCycle::class, 'admission_cycle_id');
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<OrgUnit, $this>
     */
    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    /**
     * @return BelongsTo<LookupValue, $this>
     */
    public function admissionType(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'admission_type_id');
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function previousSchoolCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'previous_school_country_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_id');
    }

    /**
     * @return BelongsTo<StudentRecord, $this>
     */
    public function studentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentRecord::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        self::constrainVisibleTo($query, $user);
    }

    /**
     * Staff by scope of applications.view or applications.review, and the applicant's own
     * applications. Mirrors the row level security policies on applications.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public static function constrainVisibleTo(Builder $query, User $user): void
    {
        $authorization = app(AuthorizationService::class);
        $scope = $authorization->scopeFor($user, Permission::ApplicationsView)
            ->merge($authorization->scopeFor($user, Permission::ApplicationsReview));

        if ($scope->isGlobal) {
            return;
        }

        $query->where(function (Builder $query) use ($scope, $user) {
            $query->whereRaw('false');

            if ($scope->orgUnitIds !== []) {
                $query->orWhereIn('applications.org_unit_id', $scope->orgUnitIds);
            }

            if ($scope->programIds !== []) {
                $query->orWhereIn('applications.program_id', $scope->programIds);
            }

            if ($user->person_id !== null) {
                $query->orWhere('applications.person_id', $user->person_id);
            }
        });
    }

    public function isOwnedBy(User $user): bool
    {
        return $user->person_id !== null && $user->person_id === $this->person_id;
    }

    /**
     * Whether the user holds the permission in a scope that covers this application.
     */
    public function isInScopeOf(User $user, Permission $permission): bool
    {
        return app(AuthorizationService::class)->scopeFor($user, $permission)->covers($this->org_unit_id, $this->program_id);
    }

    /**
     * The decision as the applicant may see it: hidden until released.
     */
    public function isDecisionVisibleToApplicant(): bool
    {
        return $this->decision_released_at !== null;
    }

    /**
     * Status as shown to the applicant: a decided but unreleased application still looks
     * under review.
     */
    public function applicantStatus(): ApplicationStatus
    {
        return $this->status->isDecided() && ! $this->isDecisionVisibleToApplicant() ? ApplicationStatus::UnderReview : $this->status;
    }
}
