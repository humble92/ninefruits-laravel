<?php

namespace App\Models\Graduation;

use App\Domain\Curriculum\Enums\ProgramRole;
use App\Models\Curriculum\CurriculumRequirementSet;
use App\Models\Organization\Program;
use App\Models\Person\StudentRecord;
use App\Models\Term\Term;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A stored degree audit: what the student had achieved against the requirement set at
 * that moment. Graduation is processed from the latest stored audit.
 *
 * @property int $id
 * @property int $student_record_id
 * @property int|null $curriculum_requirement_set_id
 * @property ProgramRole $program_role
 * @property int $program_id
 * @property int|null $term_id
 * @property bool $is_satisfied
 * @property string $earned_credits
 * @property string|null $required_credits
 * @property string|null $cumulative_gpa
 * @property array<string, mixed> $result
 * @property CarbonImmutable $audited_at
 * @property int|null $audited_by_id
 * @property-read StudentRecord $studentRecord
 * @property-read CurriculumRequirementSet|null $requirementSet
 * @property-read Program $program
 * @property-read Term|null $term
 * @property-read User|null $auditedBy
 */
class DegreeAudit extends Model
{
    protected $fillable = [
        'student_record_id', 'curriculum_requirement_set_id', 'program_role', 'program_id', 'term_id', 'is_satisfied', 'earned_credits', 'required_credits',
        'cumulative_gpa', 'result', 'audited_at', 'audited_by_id',
    ];

    protected $attributes = ['curriculum_requirement_set_id' => null, 'term_id' => null, 'required_credits' => null, 'cumulative_gpa' => null, 'audited_by_id' => null];

    protected function casts(): array
    {
        return [
            'program_role' => ProgramRole::class, 'is_satisfied' => 'boolean', 'earned_credits' => 'decimal:1', 'required_credits' => 'decimal:1',
            'cumulative_gpa' => 'decimal:2', 'result' => 'array', 'audited_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<StudentRecord, $this>
     */
    public function studentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentRecord::class);
    }

    /**
     * @return BelongsTo<CurriculumRequirementSet, $this>
     */
    public function requirementSet(): BelongsTo
    {
        return $this->belongsTo(CurriculumRequirementSet::class, 'curriculum_requirement_set_id');
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function auditedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'audited_by_id');
    }

    /**
     * The newest audit per student record and role.
     *
     * @param  Builder<DegreeAudit>  $query
     */
    public function scopeNewestPerStudent(Builder $query): void
    {
        $query->whereIn('id', self::query()->selectRaw('max(id)')->groupBy('student_record_id', 'program_role'));
    }
}
