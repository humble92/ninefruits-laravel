<?php

use App\Domain\Grading\Data\GradedCourse;
use App\Domain\Grading\Services\GpaCalculator;
use App\Domain\Grading\Settings\GradingSettings;

function gpaCalculator(int $decimals = 2, string $rounding = 'round', string $retakes = 'latest'): GpaCalculator
{
    $settings = app(GradingSettings::class);
    $settings->gpa_decimals = $decimals;
    $settings->gpa_rounding = $rounding;
    $settings->retake_policy = $retakes;

    return new GpaCalculator($settings);
}

/**
 * @param  float|null  $points  null for P/NP/I
 */
function gradedCourse(int $id, float $credits, ?float $points, bool $passed = true, ?int $repeats = null, string $termStart = '2026-03-02', ?bool $countsInGpa = null): GradedCourse
{
    return new GradedCourse($id, 1, $termStart, (int) round($credits * 10), $points === null ? null : (int) round($points * 100), $countsInGpa ?? $points !== null, $passed, $repeats);
}

it('rounds half up and cuts at the configured decimals', function (int $decimals, string $rounding, array $courses, ?string $expected) {
    expect(gpaCalculator($decimals, $rounding)->totals($courses)->gpa)->toBe($expected);
})->with([
    // 3 × 4.0 + 3 × 3.5 + 2 × 2.5 = 27.5 over 8 credits = 3.4375
    'two decimals, round' => [2, 'round', [gradedCourse(1, 3, 4.0), gradedCourse(2, 3, 3.5), gradedCourse(3, 2, 2.5)], '3.44'],
    'two decimals, floor' => [2, 'floor', [gradedCourse(1, 3, 4.0), gradedCourse(2, 3, 3.5), gradedCourse(3, 2, 2.5)], '3.43'],
    'three decimals, exact half' => [3, 'round', [gradedCourse(1, 3, 4.0), gradedCourse(2, 3, 3.5), gradedCourse(3, 2, 2.5)], '3.438'],
    // 4.3 × 1 + 1.7 × 1 = 6.0 over 2 = 3.000: no float error at the boundary
    'legacy 4.3 points' => [3, 'round', [gradedCourse(1, 1, 4.3), gradedCourse(2, 1, 1.7)], '3.000'],
    // 4.0 + 2 × 3.5 = 11 over 3 credits = 3.666…
    'repeating decimal floor' => [2, 'floor', [gradedCourse(1, 1, 4.0), gradedCourse(2, 2, 3.5)], '3.66'],
    'repeating decimal round' => [2, 'round', [gradedCourse(1, 1, 4.0), gradedCourse(2, 2, 3.5)], '3.67'],
    'zero decimals' => [0, 'round', [gradedCourse(1, 3, 4.5), gradedCourse(2, 3, 3.5)], '4'],
    'F counts with 0 points' => [2, 'round', [gradedCourse(1, 3, 4.5), gradedCourse(2, 3, 0.0, passed: false)], '2.25'],
    'only P/F: no GPA' => [2, 'round', [gradedCourse(1, 2, null), gradedCourse(2, 1, null, passed: false)], null],
    'nothing: no GPA' => [2, 'round', [], null],
    'half credits' => [2, 'round', [gradedCourse(1, 0.5, 4.5), gradedCourse(2, 1.5, 3.0)], '3.38'],
]);

it('counts credits as attempted, earned and graded', function () {
    $totals = gpaCalculator()->totals([
        gradedCourse(1, 3, 4.0),                    // A0
        gradedCourse(2, 3, 0.0, passed: false),     // F: attempted, GPA, not earned
        gradedCourse(3, 2, null),                   // P: attempted, earned, not GPA
        gradedCourse(4, 1, null, passed: false),    // NP or I: attempted only
    ]);

    expect($totals)
        ->attemptedCredits->toBe('9.0')
        ->earnedCredits->toBe('5.0')
        ->gpaCredits->toBe('6.0')
        ->gradePointsTotal->toBe('12.00')
        ->gpa->toBe('2.00');
});

it('counts only the latest attempt of a retaken course in the cumulative GPA', function () {
    $courses = [
        gradedCourse(1, 3, 1.0, termStart: '2025-03-02'),                       // D0
        gradedCourse(2, 3, 0.0, passed: false, repeats: 1, termStart: '2025-09-01'), // F on the retake
        gradedCourse(3, 3, 4.0, repeats: 2, termStart: '2026-03-02'),           // A0 on the second retake
        gradedCourse(4, 3, 3.0, termStart: '2025-03-02'),
    ];

    $calculator = gpaCalculator();

    expect(array_keys($calculator->superseded($courses)))->toEqualCanonicalizing([1, 2])
        ->and($calculator->cumulative($courses))
        ->attemptedCredits->toBe('6.0')
        ->gpa->toBe('3.50');
});

it('keeps the latest attempt even when it is worse, or the best one by policy', function () {
    $courses = [gradedCourse(1, 3, 4.0, termStart: '2025-03-02'), gradedCourse(2, 3, 2.0, repeats: 1, termStart: '2026-03-02')];

    expect(gpaCalculator()->cumulative($courses)->gpa)->toBe('2.00')
        ->and(gpaCalculator(retakes: 'best')->cumulative($courses)->gpa)->toBe('4.00');
});

it('keeps a term GPA with every grade of the term', function () {
    // The retaken attempt still counts in its own term's GPA.
    $calculator = gpaCalculator();
    $earlier = [gradedCourse(1, 3, 1.0, termStart: '2025-03-02'), gradedCourse(4, 3, 3.0, termStart: '2025-03-02')];

    expect($calculator->totals($earlier)->gpa)->toBe('2.00');
});
