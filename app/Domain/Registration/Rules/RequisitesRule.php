<?php

namespace App\Domain\Registration\Rules;

use App\Domain\Curriculum\Enums\RequisiteKind;
use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Data\RuleCheck;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;
use App\Domain\Registration\Services\CourseHistory;
use App\Models\Curriculum\CourseRequisite;
use Illuminate\Support\Collection;

/**
 * Prerequisites and corequisites of the course version (legacy SPCOURSE): in each group
 * one course (or an equivalent valid now) must be passed, at the minimum grade if set; a
 * prerequisite marked allows_concurrent, and a corequisite, may also be taken this term.
 * Courses taken on the person's earlier student records count.
 */
class RequisitesRule implements RegistrationRule
{
    public function __construct(private readonly CourseHistory $history) {}

    public function code(): RuleCode
    {
        return RuleCode::Requisites;
    }

    public function defaults(): array
    {
        return ['severity' => RuleSeverity::Block, 'allows_override' => true, 'parameters' => []];
    }

    public function appliesTo(AttemptAction $action): bool
    {
        return in_array($action, [AttemptAction::Enroll, AttemptAction::Waitlist, AttemptAction::Promote, AttemptAction::Cart], true);
    }

    public function check(RegistrationContext $context, array $parameters): RuleCheck
    {
        $requisites = $context->section->version->requisites()->with('requiredCourse')->get();
        $thisTerm = $context->termEnrollments()->pluck('course_id')->all();
        $missing = [];

        foreach ($requisites->groupBy(fn (CourseRequisite $requisite) => $requisite->kind->value.'-'.$requisite->group_number) as $group) {
            /** @var Collection<int, CourseRequisite> $group */
            $met = $group->contains(function (CourseRequisite $requisite) use ($context, $thisTerm) {
                $accepted = $this->history->acceptedCourseIds($requisite->required_course_id, $context->at);
                $concurrent = $requisite->kind === RequisiteKind::Corequisite || $requisite->allows_concurrent;

                return $this->history->hasPassed($context->record, $accepted, $requisite->minimum_grade)
                    || ($concurrent && array_intersect($accepted, $thisTerm) !== []);
            });

            if (! $met) {
                $missing[] = $group->map(fn (CourseRequisite $requisite) => $requisite->requiredCourse->code)->implode(' / ');
            }
        }

        return $missing === []
            ? RuleCheck::pass()
            : RuleCheck::fail(__('registration.rules.requisites.failed', ['courses' => implode(', ', $missing)]), ['missing' => $missing]);
    }
}
