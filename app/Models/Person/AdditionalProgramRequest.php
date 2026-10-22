<?php

namespace App\Models\Person;

use App\Domain\Person\Enums\AdditionalProgramRequestStatus;
use App\Domain\Person\Enums\AdditionalProgramRole;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuthorizationService;
use App\Models\Organization\Program;
use App\Models\Term\Term;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student's application for a second major or minor, decided by staff of the target
 * program. Approval adds a student_record_programs row starting with the term.
 *
 * @property int $id
 * @property int $student_record_id
 * @property int $program_id
 * @property int $org_unit_id
 * @property AdditionalProgramRole $role
 * @property int $term_id
 * @property AdditionalProgramRequestStatus $status
 * @property string|null $statement
 * @property int|null $requested_by_id
 * @property CarbonImmutable $requested_at
 * @property int|null $decided_by_id
 * @property CarbonImmutable|null $decided_at
 * @property string|null $decision_note
 * @property int|null $student_record_program_id
 * @property-read StudentRecord $studentRecord
 * @property-read Program $program
 * @property-read Term $term
 * @property-read User|null $requestedBy
 * @property-read User|null $decidedBy
 */
class AdditionalProgramRequest extends Model
{
    protected $fillable = ['student_record_id', 'program_id', 'org_unit_id', 'role', 'term_id', 'statement', 'requested_by_id', 'requested_at'];

    protected $attributes = [
        'status' => 'requested', 'statement' => null, 'decided_by_id' => null, 'decided_at' => null, 'decision_note' => null,
        'student_record_program_id' => null,
    ];

    protected function casts(): array
    {
        return [
            'role' => AdditionalProgramRole::class,
            'status' => AdditionalProgramRequestStatus::class,
            'requested_at' => 'immutable_datetime',
            'decided_at' => 'immutable_datetime',
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
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_id');
    }

    /**
     * Requests the user may decide (additional_programs.approve covering the target program).
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public static function constrainDecidableBy(Builder $query, User $user): void
    {
        $scope = app(AuthorizationService::class)->scopeFor($user, Permission::AdditionalProgramsApprove);

        if ($scope->isGlobal) {
            return;
        }

        $query->where(fn (Builder $query) => $query->whereRaw('false')
            ->when($scope->orgUnitIds !== [], fn (Builder $query) => $query->orWhereIn('additional_program_requests.org_unit_id', $scope->orgUnitIds))
            ->when($scope->programIds !== [], fn (Builder $query) => $query->orWhereIn('additional_program_requests.program_id', $scope->programIds)));
    }

    public function isDecidableBy(User $user): bool
    {
        return app(AuthorizationService::class)->scopeFor($user, Permission::AdditionalProgramsApprove)->covers($this->org_unit_id, $this->program_id);
    }
}
