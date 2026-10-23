<?php

use App\Domain\Grading\Actions\CloseTerm;
use App\Domain\Grading\Actions\ManageGradeChanges;
use App\Domain\Grading\Enums\AcademicStanding;
use App\Domain\Grading\Settings\GradingSettings;
use App\Domain\Security\Enums\Permission;
use App\Domain\Term\Enums\TermStatus;
use App\Models\Grading\GradeScale;
use App\Models\Grading\TermRecord;
use App\Models\Offering\Section;
use App\Models\Organization\Program;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\Term\TermType;
use App\Models\User;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    GradeScale::factory()->korean()->create();
    $this->staff = User::factory()->withPermissions([Permission::GradesManage, Permission::GradeChangesApprove])->create();
    $this->regular = TermType::factory()->create(['is_regular' => true]);
    $this->summer = TermType::factory()->create(['is_regular' => false]);
    $this->program = Program::factory()->create();
    $settings = app(GradingSettings::class);
    $settings->warning_gpa_below = 1.75;
    $settings->dismissal_warning_count = 3;
    $settings->honors_min_gpa = 4.0;
    $settings->honors_min_credits = 6;
    $settings->save();
});

function closingTerm(TermType $type, string $start): Term
{
    return Term::factory()->published()->between($start, date('Y-m-d', strtotime($start.' +100 days')))->create(['term_type_id' => $type->id]);
}

/**
 * Finalized grades of the student in the term, 3 credits each.
 *
 * @param  list<string>  $grades
 */
function finalGrades(StudentRecord $record, Term $term, array $grades): void
{
    foreach ($grades as $grade) {
        Enrollment::factory()->inSection(Section::factory()->published()->inTerm($term)->create())->completed($grade)->create(['student_record_id' => $record->id, 'credits' => 3]);
    }
}

function closeNow(Term $term, User $staff): TermRecord
{
    app(CloseTerm::class)->close($term->refresh(), $staff, 'End of term');

    return TermRecord::query()->where('term_id', $term->id)->firstOrFail();
}

it('warns below the threshold and flags dismissal review after the third warning in all terms', function () {
    $student = StudentRecord::factory()->inProgram($this->program)->create();
    $grades = [['D0', 'F'], ['B0', 'B0'], ['D0', 'D0'], ['F', 'D+']];
    $standings = [];

    foreach ($grades as $index => $termGrades) {
        $term = closingTerm($this->regular, (2023 + $index).'-03-01');
        finalGrades($student, $term, $termGrades);
        $record = closeNow($term, $this->staff);
        $standings[] = [$record->academic_standing, $record->warning_count];

        expect($term->refresh()->status)->toBe(TermStatus::GradesClosed)->and($record->closed_at)->not->toBeNull();
    }

    expect($standings)->toBe([
        [AcademicStanding::Warning, 1],
        [AcademicStanding::Good, 1],
        [AcademicStanding::Warning, 2],
        [AcademicStanding::DismissalReview, 3],
    ])->and($student->refresh()->academic_standing)->toBe('dismissal_review');
});

it('counts only consecutive warnings when set, and leaves summer terms out', function () {
    $settings = app(GradingSettings::class);
    $settings->dismissal_consecutive_only = true;
    $settings->dismissal_warning_count = 2;
    $settings->save();
    $student = StudentRecord::factory()->inProgram($this->program)->create();

    $spring = closingTerm($this->regular, '2025-03-01');
    finalGrades($student, $spring, ['F', 'D0']);
    $summer = closingTerm($this->summer, '2025-06-25');
    finalGrades($student, $summer, ['F']);
    $fall = closingTerm($this->regular, '2025-09-01');
    finalGrades($student, $fall, ['D0', 'D0']);

    expect(closeNow($spring, $this->staff)->academic_standing)->toBe(AcademicStanding::Warning)
        ->and(closeNow($summer, $this->staff))->academic_standing->toBeNull()->warning_count->toBe(1)
        ->and(closeNow($fall, $this->staff))->academic_standing->toBe(AcademicStanding::DismissalReview)->warning_count->toBe(2);
});

it('gives no warning for a term of pass/fail courses only', function () {
    $student = StudentRecord::factory()->inProgram($this->program)->create();
    $term = closingTerm($this->regular, '2025-03-01');
    finalGrades($student, $term, ['P', 'NP']);

    expect(closeNow($term, $this->staff))->term_gpa->toBeNull()->academic_standing->toBe(AcademicStanding::Good);
});

it('ranks honors per program with ties and without failing students', function () {
    $term = closingTerm($this->regular, '2025-03-01');
    $students = [
        'first' => ['A+', 'A+'],      // 4.50
        'tied' => ['A+', 'A+'],       // 4.50
        'third' => ['A+', 'A0'],      // 4.25
        'failed one' => ['A+', 'A+', 'NP'], // 4.50, but a failed course
        'few credits' => ['A+'],      // 3 credits < 6
    ];
    $records = [];

    foreach ($students as $name => $grades) {
        $records[$name] = StudentRecord::factory()->inProgram($this->program)->create();
        finalGrades($records[$name], $term, $grades);
    }

    $other = StudentRecord::factory()->inProgram(Program::factory()->create())->create();
    finalGrades($other, $term, ['A0', 'A0']);

    app(CloseTerm::class)->close($term, $this->staff);
    $rank = fn (StudentRecord $record) => TermRecord::query()->where('student_record_id', $record->id)->sole()->honors_rank;

    expect($rank($records['first']))->toBe(1)
        ->and($rank($records['tied']))->toBe(1)
        ->and($rank($records['third']))->toBe(3)
        ->and($rank($records['failed one']))->toBeNull()
        ->and($rank($records['few credits']))->toBeNull()
        ->and($rank($other))->toBe(1);
});

it('does not close a term with grades that are not final', function () {
    $term = closingTerm($this->regular, '2025-03-01');
    Enrollment::factory()->inSection(Section::factory()->published()->inTerm($term)->create())->create();

    expect(app(CloseTerm::class)->preflight($term)->openSections)->toHaveCount(1)
        ->and(fn () => app(CloseTerm::class)->close($term, $this->staff))->toThrow(ValidationException::class)
        ->and($term->refresh()->status)->toBe(TermStatus::Published);
});

it('recalculates later cumulative GPAs when an earlier grade changes', function () {
    $student = StudentRecord::factory()->inProgram($this->program)->create();
    $first = closingTerm($this->regular, '2025-03-01');
    finalGrades($student, $first, ['C0']);
    $second = closingTerm($this->regular, '2025-09-01');
    finalGrades($student, $second, ['A0']);
    closeNow($first, $this->staff);
    expect(closeNow($second, $this->staff)->cumulative_gpa)->toBe('3.00');

    $enrollment = Enrollment::query()->where('term_id', $first->id)->sole();
    $changes = app(ManageGradeChanges::class);
    $changes->approve($changes->request($enrollment, 'B0', 'Exam re-marked', User::factory()->withPermissions([Permission::GradeChangesRequest])->create()), $this->staff);

    expect(TermRecord::query()->where('term_id', $second->id)->sole())
        ->cumulative_gpa->toBe('3.50')
        ->closed_at->not->toBeNull();
});
