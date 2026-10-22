<?php

use App\Models\Grading\GradeScale;
use App\Models\Grading\GradeScaleItem;
use App\Models\Term\AcademicYear;
use App\Models\Term\Term;
use App\Models\Term\TermType;
use App\Models\Term\TermWindow;

it('rejects overlapping academic years', function () {
    AcademicYear::factory()->forYear(2026)->create();

    expect(databaseError(fn () => AcademicYear::factory()->create(['code' => 'X', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31'])))
        ->toContain('academic_years_no_overlap_excl');
});

it('rejects overlapping terms of the same type but allows different types', function () {
    $year = AcademicYear::factory()->forYear(2026)->create();
    $spring = TermType::factory()->create();
    $summer = TermType::factory()->create();

    Term::factory()->for($year)->create(['term_type_id' => $spring->id, 'code' => '2026-SP', 'start_date' => '2026-03-02', 'end_date' => '2026-06-20']);

    expect(databaseError(fn () => Term::factory()->create(['academic_year_id' => AcademicYear::factory()->forYear(2030)->create()->id, 'term_type_id' => $spring->id, 'code' => 'X', 'start_date' => '2026-06-01', 'end_date' => '2026-07-01'])))
        ->toContain('terms_no_overlap_excl')
        ->and(databaseError(fn () => Term::factory()->for($year)->create(['term_type_id' => $summer->id, 'code' => '2026-SU', 'start_date' => '2026-06-15', 'end_date' => '2026-07-31'])))
        ->toBeNull();
});

it('requires a window to close after it opens', function () {
    expect(databaseError(fn () => TermWindow::factory()->between(now(), now()->subHour())->create()))->toContain('term_windows_order_check');
});

it('allows only one default grade scale', function () {
    GradeScale::factory()->create(['is_default' => true]);

    expect(databaseError(fn () => GradeScale::factory()->create(['is_default' => true])))->toContain('grade_scales_single_default_unique');
});

it('requires grade points for grades counted in the GPA', function () {
    expect(databaseError(fn () => GradeScaleItem::factory()->create(['code' => 'X', 'grade_points' => null, 'counts_in_gpa' => true])))
        ->toContain('grade_scale_items_gpa_points_check')
        ->and(databaseError(fn () => GradeScaleItem::factory()->create(['code' => 'P', 'grade_points' => null, 'counts_in_gpa' => false])))
        ->toBeNull();
});
