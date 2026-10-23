<?php

namespace App\Domain\Assessment\Services;

use App\Domain\Curriculum\Enums\ProgramRole;
use App\Domain\Curriculum\Enums\RequirementKind;
use App\Models\Assessment\AssessmentResult;
use App\Models\Assessment\AssessmentType;
use App\Models\Curriculum\CurriculumRequirement;
use App\Models\Curriculum\CurriculumRequirementSet;
use App\Models\Person\StudentRecord;
use Illuminate\Support\Collection;

/**
 * Whether a student has passed a kind (any passing result, from a session or external),
 * and which kinds the student's curriculum requires. Graduation checks (Phase 11) call
 * this for "assessment_pass" requirements.
 */
class AssessmentPassCheck
{
    public function hasPassed(StudentRecord $record, AssessmentType $type): bool
    {
        return AssessmentResult::query()->where('student_record_id', $record->id)->where('assessment_type_id', $type->id)->where('is_passed', true)->exists();
    }

    /**
     * The kinds the student's primary curriculum requires, with the pass state.
     *
     * @return Collection<int, array{type: AssessmentType, passed: bool}>
     */
    public function required(StudentRecord $record): Collection
    {
        $set = CurriculumRequirementSet::query()->where('program_id', $record->program_id)->where('program_role', ProgramRole::Primary)
            ->forCatalogYear($record->catalog_year)->first();

        if ($set === null) {
            return collect();
        }

        $passedIds = AssessmentResult::query()->where('student_record_id', $record->id)->where('is_passed', true)->pluck('assessment_type_id')->all();

        return CurriculumRequirement::query()->where('curriculum_requirement_set_id', $set->id)->where('kind', RequirementKind::AssessmentPass)
            ->with('assessmentType')->orderBy('sort_order')->get()
            ->map(fn (CurriculumRequirement $requirement) => ['type' => $requirement->assessmentType, 'passed' => in_array($requirement->assessment_type_id, $passedIds, true)])
            ->filter(fn (array $row) => $row['type'] !== null)->values();
    }
}
