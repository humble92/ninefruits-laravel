<?php

namespace App\Domain\Grading\Services;

use App\Domain\Grading\Data\CreditTotals;
use App\Domain\Grading\Data\GradedCourse;
use App\Domain\Grading\Enums\GpaRounding;
use App\Domain\Grading\Enums\RetakePolicy;
use App\Domain\Grading\Settings\GradingSettings;

/**
 * Term and cumulative GPA (legacy SV_AVR_PRIPNT, SV_AVR_POINT views).
 *
 * - Attempted: every graded course. Earned: passed courses. GPA credits: courses whose
 *   grade counts in the GPA (F counts with 0 points; P, NP and I do not).
 * - GPA = sum(points × credits) / GPA credits, to the configured decimals, rounded half up
 *   or cut. Integer arithmetic, so 3.0045 rounds to 3.005 and never to 3.004.
 * - Cumulative: of the attempts of a retaken course (linked by repeats_enrollment_id), only
 *   one counts by the retake policy; the others leave the cumulative totals entirely.
 */
class GpaCalculator
{
    public function __construct(private readonly GradingSettings $settings) {}

    /**
     * @param  iterable<GradedCourse>  $courses
     */
    public function totals(iterable $courses): CreditTotals
    {
        $attempted = $earned = $gpaCredits = $points = 0;

        foreach ($courses as $course) {
            $attempted += $course->creditTenths;
            $earned += $course->isPassed ? $course->creditTenths : 0;

            if ($course->countsInGpa && $course->pointHundredths !== null) {
                $gpaCredits += $course->creditTenths;
                $points += $course->pointHundredths * $course->creditTenths;
            }
        }

        return new CreditTotals(
            attemptedCredits: self::decimal($attempted, 1),
            earnedCredits: self::decimal($earned, 1),
            gpaCredits: self::decimal($gpaCredits, 1),
            gradePointsTotal: self::decimal(intdiv($points, 10), 2),
            gpa: $this->gpa($points, $gpaCredits),
        );
    }

    /**
     * Totals over all the courses with retakes resolved.
     *
     * @param  list<GradedCourse>  $courses
     */
    public function cumulative(array $courses): CreditTotals
    {
        $superseded = $this->superseded($courses);

        return $this->totals(array_filter($courses, fn (GradedCourse $course) => ! isset($superseded[$course->enrollmentId])));
    }

    /**
     * Enrollment ids that do not count in the cumulative totals because another attempt of
     * the same course counts instead.
     *
     * @param  list<GradedCourse>  $courses
     * @return array<int, true>
     */
    public function superseded(array $courses): array
    {
        $byId = [];

        foreach ($courses as $course) {
            $byId[$course->enrollmentId] = $course;
        }

        // Group attempts that are linked, directly or through each other.
        $root = [];
        $find = function (int $id) use (&$root, &$find): int {
            return ($root[$id] ?? $id) === $id ? $id : $root[$id] = $find($root[$id]);
        };

        foreach ($byId as $id => $course) {
            if ($course->repeatsEnrollmentId !== null && isset($byId[$course->repeatsEnrollmentId])) {
                $root[$find($id)] = $find($course->repeatsEnrollmentId);
            }
        }

        $groups = [];

        foreach (array_keys($byId) as $id) {
            $groups[$find($id)][] = $byId[$id];
        }

        $superseded = [];

        foreach ($groups as $attempts) {
            if (count($attempts) < 2) {
                continue;
            }

            $kept = $this->kept($attempts);

            foreach ($attempts as $attempt) {
                if ($attempt->enrollmentId !== $kept->enrollmentId) {
                    $superseded[$attempt->enrollmentId] = true;
                }
            }
        }

        return $superseded;
    }

    /**
     * @param  list<GradedCourse>  $attempts
     */
    private function kept(array $attempts): GradedCourse
    {
        usort($attempts, fn (GradedCourse $a, GradedCourse $b) => [$b->termStart, $b->enrollmentId] <=> [$a->termStart, $a->enrollmentId]);

        if ($this->settings->retakePolicy() === RetakePolicy::Latest) {
            return $attempts[0];
        }

        // Best: the highest grade that counts in the GPA, the latest on a tie; the latest
        // attempt when none counts (e.g. all P/F).
        $graded = array_values(array_filter($attempts, fn (GradedCourse $attempt) => $attempt->countsInGpa && $attempt->pointHundredths !== null));

        if ($graded === []) {
            return $attempts[0];
        }

        $best = $graded[0];

        foreach ($graded as $attempt) {
            if ($attempt->pointHundredths > $best->pointHundredths) {
                $best = $attempt;
            }
        }

        return $best;
    }

    /**
     * @param  int  $pointCreditThousandths  sum of points (hundredths) × credits (tenths)
     */
    private function gpa(int $pointCreditThousandths, int $creditTenths): ?string
    {
        if ($creditTenths === 0) {
            return null;
        }

        $decimals = max(0, min(3, $this->settings->gpa_decimals));
        $numerator = $pointCreditThousandths * 10 ** $decimals;
        $denominator = $creditTenths * 100;
        $scaled = $this->settings->rounding() === GpaRounding::Round
            ? intdiv(2 * $numerator + $denominator, 2 * $denominator)
            : intdiv($numerator, $denominator);

        return self::decimal($scaled, $decimals);
    }

    private static function decimal(int $scaled, int $decimals): string
    {
        if ($decimals === 0) {
            return (string) $scaled;
        }

        $digits = str_pad((string) abs($scaled), $decimals + 1, '0', STR_PAD_LEFT);

        return ($scaled < 0 ? '-' : '').substr($digits, 0, -$decimals).'.'.substr($digits, -$decimals);
    }
}
