<?php

namespace App\Models\Person;

use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuthorizationService;
use App\Models\Institution\LookupValue;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\Program;
use App\Models\Term\Term;
use App\Models\User;
use App\Support\Targeting\TargetSubject;
use Carbon\CarbonImmutable;
use Database\Factories\Person\StudentRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One admission of a person: a new student number each time (docs/porting-plan.md 10.1).
 * Graduation changes the status instead of moving the row (legacy SRREGISTRY → SGREGISTRY).
 *
 * @property int $id
 * @property int $person_id
 * @property string $student_number
 * @property int $program_id
 * @property int $org_unit_id
 * @property int|null $admission_term_id
 * @property int|null $admission_type_id
 * @property CarbonImmutable $admission_date
 * @property int $catalog_year
 * @property int $year_level
 * @property StudentStatus $status
 * @property CarbonImmutable|null $status_changed_at
 * @property string|null $academic_standing
 * @property CarbonImmutable|null $graduation_date
 * @property string|null $degree_number
 * @property string|null $institutional_email
 * @property list<string>|null $tags
 * @property-read Person $person
 * @property-read Program $program
 * @property-read OrgUnit $orgUnit
 */
class StudentRecord extends Model implements TargetSubject
{
    /** @use HasFactory<StudentRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'person_id', 'student_number', 'program_id', 'org_unit_id', 'admission_term_id', 'admission_type_id', 'admission_date',
        'catalog_year', 'year_level', 'institutional_email', 'tags',
    ];

    protected $attributes = [
        'status' => 'enrolled', 'year_level' => 1, 'admission_term_id' => null, 'admission_type_id' => null, 'status_changed_at' => null,
        'academic_standing' => null, 'graduation_date' => null, 'degree_number' => null, 'institutional_email' => null, 'tags' => null,
    ];

    protected function casts(): array
    {
        return [
            'admission_date' => 'immutable_date',
            'graduation_date' => 'immutable_date',
            'status_changed_at' => 'immutable_datetime',
            'status' => StudentStatus::class,
            'catalog_year' => 'integer',
            'year_level' => 'integer',
            'tags' => 'array',
        ];
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
     * @return BelongsTo<Term, $this>
     */
    public function admissionTerm(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'admission_term_id');
    }

    /**
     * @return BelongsTo<LookupValue, $this>
     */
    public function admissionType(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'admission_type_id');
    }

    /**
     * @return HasMany<StudentRecordProgram, $this>
     */
    public function additionalPrograms(): HasMany
    {
        return $this->hasMany(StudentRecordProgram::class);
    }

    /**
     * @return HasMany<StudentStatusChange, $this>
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(StudentStatusChange::class)->orderByDesc('requested_at');
    }

    /**
     * @return HasMany<AdvisorAssignment, $this>
     */
    public function advisorAssignments(): HasMany
    {
        return $this->hasMany(AdvisorAssignment::class)->orderByDesc('start_date');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', StudentStatus::active());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        self::constrainVisibleTo($query, $user);
    }

    /**
     * Records the user may see: staff by scope (student_records.view or .manage: all, org
     * unit and below, program), the student's own records, and current advisees. Mirrors
     * the row level security policies on student_records.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public static function constrainVisibleTo(Builder $query, User $user): void
    {
        $authorization = app(AuthorizationService::class);
        $scope = $authorization->scopeFor($user, Permission::StudentRecordsView)
            ->merge($authorization->scopeFor($user, Permission::StudentRecordsManage));

        if ($scope->isGlobal) {
            return;
        }

        $query->where(function (Builder $query) use ($scope, $user) {
            $query->whereRaw('false');

            if ($scope->orgUnitIds !== []) {
                $query->orWhereIn('student_records.org_unit_id', $scope->orgUnitIds);
            }

            if ($scope->programIds !== []) {
                $query->orWhereIn('student_records.program_id', $scope->programIds);
            }

            if ($user->person_id !== null) {
                $query->orWhere('student_records.person_id', $user->person_id)
                    ->orWhereIn('student_records.id', AdvisorAssignment::query()
                        ->current()
                        ->whereHas('instructor', fn (Builder $instructor) => $instructor->where('person_id', $user->person_id))
                        ->select('student_record_id'));
            }
        });
    }

    public function isVisibleTo(User $user): bool
    {
        return self::query()->visibleTo($user)->whereKey($this->id)->exists();
    }

    /**
     * Whether the user may change this record (student_records.manage in a scope that
     * covers it).
     */
    public function isManageableBy(User $user): bool
    {
        $scope = app(AuthorizationService::class)->scopeFor($user, Permission::StudentRecordsManage);

        return $scope->isGlobal
            || in_array($this->org_unit_id, $scope->orgUnitIds, true)
            || in_array($this->program_id, $scope->programIds, true);
    }

    public function targetProgramIds(): array
    {
        return [$this->program_id, ...$this->additionalPrograms()->whereNull('end_date')->pluck('program_id')->all()];
    }

    public function targetOrgUnitIds(): array
    {
        return OrgUnit::query()->findOrFail($this->org_unit_id)->ancestorIds();
    }

    public function targetAdmissionYear(): ?int
    {
        return $this->admission_date->year;
    }

    public function targetYearLevel(): ?int
    {
        return $this->year_level;
    }

    public function targetStudentStatus(): ?string
    {
        return $this->status->value;
    }

    public function targetAcademicStanding(): ?string
    {
        return $this->academic_standing;
    }

    public function targetTags(): array
    {
        return array_map('strval', $this->tags ?? []);
    }
}
