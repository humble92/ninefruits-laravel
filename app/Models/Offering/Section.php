<?php

namespace App\Models\Offering;

use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Security\Data\PermissionScope;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuthorizationService;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseVersion;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\Program;
use App\Models\Person\Instructor;
use App\Models\Term\Term;
use App\Models\Term\TermSession;
use App\Models\User;
use App\Support\Targeting\TargetCriteria;
use App\Support\Targeting\TargetSubject;
use Database\Factories\Offering\SectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A course offered in a term session (legacy SFOPOBJECT). Its deadlines are the session's
 * windows. Course data (title, credits) comes from the version valid at the session start.
 *
 * @property int $id
 * @property int $term_id
 * @property int $term_session_id
 * @property int $course_id
 * @property int $course_version_id
 * @property string $section_code
 * @property int $org_unit_id
 * @property int $capacity
 * @property SectionStatus $status
 * @property TargetCriteria $eligibility
 * @property TargetCriteria $exclusion
 * @property string|null $instruction_language
 * @property string|null $note
 * @property int|null $copied_from_section_id
 * @property-read Term $term
 * @property-read TermSession $session
 * @property-read Course $course
 * @property-read CourseVersion $version
 * @property-read OrgUnit $orgUnit
 * @property-read Collection<int, SectionInstructor> $instructorAssignments
 * @property-read Collection<int, Instructor> $instructors
 * @property-read Collection<int, SectionMeeting> $meetings
 * @property-read Collection<int, SectionSeatGroup> $seatGroups
 * @property-read Syllabus|null $syllabus
 */
class Section extends Model implements Translatable
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['note'];

    protected $fillable = [
        'term_id', 'term_session_id', 'course_id', 'course_version_id', 'section_code', 'org_unit_id', 'capacity',
        'eligibility', 'exclusion', 'instruction_language', 'note',
    ];

    protected $attributes = [
        'status' => 'draft', 'eligibility' => null, 'exclusion' => null, 'instruction_language' => null, 'note' => null,
        'copied_from_section_id' => null,
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'status' => SectionStatus::class,
            'eligibility' => TargetCriteria::class,
            'exclusion' => TargetCriteria::class,
        ];
    }

    /**
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * @return BelongsTo<TermSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TermSession::class, 'term_session_id');
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<CourseVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(CourseVersion::class, 'course_version_id');
    }

    /**
     * @return BelongsTo<OrgUnit, $this>
     */
    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    /**
     * @return HasMany<SectionInstructor, $this>
     */
    public function instructorAssignments(): HasMany
    {
        return $this->hasMany(SectionInstructor::class);
    }

    /**
     * @return BelongsToMany<Instructor, $this>
     */
    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(Instructor::class, 'section_instructors')->withPivot('role')->withTimestamps();
    }

    /**
     * @return HasMany<SectionMeeting, $this>
     */
    public function meetings(): HasMany
    {
        return $this->hasMany(SectionMeeting::class)->orderBy('weekday')->orderBy('start_time');
    }

    /**
     * @return HasMany<SectionSeatGroup, $this>
     */
    public function seatGroups(): HasMany
    {
        return $this->hasMany(SectionSeatGroup::class)->orderBy('sort_order');
    }

    /**
     * @return HasOne<Syllabus, $this>
     */
    public function syllabus(): HasOne
    {
        return $this->hasOne(Syllabus::class);
    }

    /**
     * "CSE2010-01".
     */
    public function label(): string
    {
        return $this->course->code.'-'.$this->section_code;
    }

    /**
     * Whether a student may take the section by its include and exclude conditions (seat
     * groups and registration rules are Phase 5).
     */
    public function admits(TargetSubject $subject): bool
    {
        return $this->eligibility->matches($subject) && ($this->exclusion->isEveryone() || ! $this->exclusion->matches($subject));
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', SectionStatus::Published);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeTaughtBy(Builder $query, int $personId): void
    {
        $query->whereHas('instructors', fn (Builder $instructor) => $instructor->where('person_id', $personId));
    }

    /**
     * Staff see sections of units in their sections.view or sections.manage scope. A
     * program scope means the program's unit (sections belong to units, not programs).
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public static function constrainManageableBy(Builder $query, User $user): void
    {
        $authorization = app(AuthorizationService::class);
        $scope = $authorization->scopeFor($user, Permission::SectionsView)->merge($authorization->scopeFor($user, Permission::SectionsManage));

        if ($scope->isGlobal) {
            return;
        }

        $query->whereIn('sections.org_unit_id', self::scopeOrgUnitIds($scope));
    }

    public function isManageableBy(User $user): bool
    {
        $scope = app(AuthorizationService::class)->scopeFor($user, Permission::SectionsManage);

        return $scope->isGlobal || in_array($this->org_unit_id, self::scopeOrgUnitIds($scope), true);
    }

    /**
     * @return list<int>
     */
    private static function scopeOrgUnitIds(PermissionScope $scope): array
    {
        $programUnits = $scope->programIds === [] ? [] : Program::query()->whereIn('id', $scope->programIds)->pluck('org_unit_id')->all();

        return array_values(array_unique([...$scope->orgUnitIds, ...$programUnits]));
    }

    public function isTaughtBy(User $user): bool
    {
        return $user->person_id !== null && $this->instructors()->where('person_id', $user->person_id)->exists();
    }
}
