<?php

namespace App\Domain\Grading\Services;

use App\Domain\Grading\Data\GradeDistribution;
use App\Domain\Grading\Enums\EvaluationMethod;
use App\Domain\Grading\Settings\GradingSettings;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Models\Offering\Section;
use App\Models\Registration\Enrollment;

/**
 * Relative evaluation must follow the institution's grade caps (decided 2026-10-23): each
 * grade group (the letter) may take at most its cap of the section's graded students, plus
 * the tolerance. Small sections are exempt. Grades without a GPA value (P, NP, I) and
 * withdrawn students are left out of the count.
 */
class GradeDistributionCheck
{
    public function __construct(private readonly GradingSettings $settings) {}

    /**
     * Checks the grades on the section (entered or submitted). Pass unsaved grades to check
     * them instead.
     *
     * @param  list<string|null>|null  $codes
     */
    public function forSection(Section $section, ?array $codes = null): GradeDistribution
    {
        $codes ??= Enrollment::query()->where('section_id', $section->id)->where('status', EnrollmentStatus::Enrolled)
            ->where('counts_in_gpa', true)->pluck('grade_code')->all();
        $codes = array_values(array_filter($codes, fn (?string $code) => $code !== null && $code !== ''));

        return $this->check(count($codes), array_count_values(array_map(fn (string $code) => strtoupper(substr($code, 0, 1)), $codes)),
            $section->evaluation_method === EvaluationMethod::Relative);
    }

    /**
     * @param  array<string, int>  $countsByGroup
     */
    public function check(int $graded, array $countsByGroup, bool $relative = true): GradeDistribution
    {
        $minimum = $this->settings->relative_minimum_students;
        $checked = $relative && $graded >= $minimum && $graded > 0;
        $groups = [];
        $violations = [];

        foreach (array_unique([...array_keys($this->settings->relative_grade_caps), ...array_keys($countsByGroup)]) as $group) {
            $count = $countsByGroup[$group] ?? 0;
            $cap = $this->settings->relative_grade_caps[$group] ?? null;
            $percent = $graded === 0 ? 0.0 : round($count * 100 / $graded, 1);
            $limit = $cap === null ? null : $cap + $this->settings->relative_cap_tolerance_percent;
            $over = $checked && $limit !== null && $percent > $limit;
            $groups[$group] = ['count' => $count, 'percent' => $percent, 'cap' => $cap, 'limit' => $limit, 'over' => $over];

            if ($over) {
                $violations[] = __('grading.distribution.over', ['group' => $group, 'percent' => $percent, 'cap' => $cap, 'limit' => $limit]);
            }
        }

        ksort($groups);

        return new GradeDistribution($graded, $minimum, $checked, $groups, $violations);
    }
}
