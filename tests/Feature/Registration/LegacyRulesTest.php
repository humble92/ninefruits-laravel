<?php

/*
| Phase 5 completion criterion: the legacy YUST registration rules are reproduced by
| configuration alone (profile yust-legacy-registration and data), without the legacy
| bug of section 3.4 (nursing students from 2014 limited to 18 instead of 21).
*/

use App\Domain\Curriculum\Enums\RequisiteKind;
use App\Domain\Offering\Actions\SaveSectionMeeting;
use App\Domain\Registration\Actions\EnrollStudent;
use App\Domain\Registration\Actions\ManageHolds;
use App\Domain\Registration\Enums\AttemptOutcome;
use App\Domain\Registration\Enums\HoldType;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Security\Enums\Permission;
use App\Domain\Term\Enums\TermWindowType;
use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseRequisite;
use App\Models\Facility\Room;
use App\Models\Offering\Section;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\Program;
use App\Models\Person\StudentRecord;
use App\Models\Registration\CreditLimitPolicy;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\Term\TermType;
use App\Models\Term\TermWindow;
use App\Models\User;
use App\Support\Targeting\TargetCriteria;
use Database\Seeders\Essential\EssentialSeeder;
use Database\Seeders\Profiles\YustLegacyRegistrationProfileSeeder;

beforeEach(function () {
    $this->seed(EssentialSeeder::class);
    $this->nursingUnit = OrgUnit::factory()->create(['code' => 'NURS']);
    $this->otherUnit = OrgUnit::factory()->create(['code' => 'CSE']);
    $this->seed(YustLegacyRegistrationProfileSeeder::class);

    $this->nursing = Program::factory()->create(['org_unit_id' => $this->nursingUnit->id]);
    $this->other = Program::factory()->create(['org_unit_id' => $this->otherUnit->id]);
    $this->staff = User::factory()->withPermissions([Permission::RegistrationHoldsManage, Permission::SectionsManage])->create();

    $this->termOf = function (string $type): Term {
        $term = Term::factory()->published()
            ->between(now()->addMonth()->toDateString(), now()->addMonths(4)->toDateString())
            ->create(['term_type_id' => TermType::query()->where('code', $type)->value('id')]);
        TermWindow::factory()->for($term)->ofType(TermWindowType::Registration)->between(now()->subDay(), now()->addWeek())->create();

        return $term;
    };
});

function legacyStudent(Program $program, int $admissionYear): StudentRecord
{
    return StudentRecord::factory()->inProgram($program)->create(['admission_date' => "{$admissionYear}-03-01", 'catalog_year' => $admissionYear]);
}

dataset('legacy credit limits', [
    'spring, 2014 nursing (legacy bug gave 18)' => ['spring', 'nursing', 2014, 21],
    'fall, 2016 other' => ['fall', 'other', 2016, 18],
    'spring, 2013 nursing' => ['spring', 'nursing', 2013, 20],
    'fall, 2012 other' => ['fall', 'other', 2012, 17],
    'summer, 2014 nursing' => ['summer', 'nursing', 2014, 6],
    'summer, 2015 other' => ['summer', 'other', 2015, 5],
    'winter, 2013 nursing' => ['winter', 'nursing', 2013, 6],
    'summer, 2011 other' => ['summer', 'other', 2011, 6],
]);

it('applies the legacy credit limits by term type, admission year and nursing', function (string $termType, string $program, int $year, int $limit) {
    $record = legacyStudent($program === 'nursing' ? $this->nursing : $this->other, $year);

    expect((float) CreditLimitPolicy::applicableTo($record, TermType::query()->where('code', $termType)->value('id'))->max_credits)->toBe((float) $limit);
})->with('legacy credit limits');

it('lets a 2014 nursing student register 21 credits, not 18, and leaves credit bank courses out', function () {
    $term = ($this->termOf)('spring');
    $record = legacyStudent($this->nursing, 2014);
    $section = fn (string $code, int $credits) => Section::factory()->published()->inTerm($term)
        ->forCourse(Course::factory()->withVersion(['credits' => $credits])->create(['code' => $code]))->create();

    foreach (range(1, 6) as $index) {
        Enrollment::factory()->inSection($section("NUR10{$index}0", 3))->create(['student_record_id' => $record->id]);
    }

    $bank = $section('0091001', 3);
    $this->seed(YustLegacyRegistrationProfileSeeder::class); // marks credit bank courses

    expect(app(EnrollStudent::class)->handle($record, $section('NUR1980', 3), null)->succeeded())->toBeTrue() // 21: the legacy bug refused this
        ->and(app(EnrollStudent::class)->handle($record, $section('NUR1990', 3), null)->outcome)->toBe(AttemptOutcome::Refused) // 24 > 21
        ->and(app(EnrollStudent::class)->handle($record, $bank->refresh(), null)->succeeded())->toBeTrue();
});

it('reproduces the other legacy checks with the default rule set', function () {
    $term = ($this->termOf)('fall');
    $record = legacyStudent($this->other, 2016);
    $planner = User::factory()->withPermissions([Permission::SectionsManage])->create();
    $meet = fn (Section $section, string $start, string $end) => app(SaveSectionMeeting::class)
        ->handle($section, ['weekday' => 2, 'start_time' => $start, 'end_time' => $end, 'room_id' => Room::factory()->create()->id], $planner);
    $refusedFor = fn (Section $section) => collect(app(EnrollStudent::class)->handle($record, $section->refresh(), null)->evaluation->results)
        ->filter->blocks()->map(fn ($result) => $result->code)->values()->all();

    // TARGET_ST_DEPART / TARGET_ST_GRADE: department and admission year restrictions.
    $nursingOnly = Section::factory()->published()->inTerm($term)->create(['eligibility' => new TargetCriteria(orgUnitIds: [$this->nursingUnit->id])]);
    $from2018 = Section::factory()->published()->inTerm($term)->create(['eligibility' => new TargetCriteria(admissionYearFrom: 2018)]);
    expect($refusedFor($nursingOnly))->toBe([RuleCode::Eligibility])
        ->and($refusedFor($from2018))->toBe([RuleCode::Eligibility]);

    // SPCOURSE: prerequisite not passed.
    $advanced = Section::factory()->published()->inTerm($term)->create();
    CourseRequisite::query()->create(['course_version_id' => $advanced->course_version_id, 'kind' => RequisiteKind::Prerequisite, 'required_course_id' => Course::factory()->create()->id]);
    expect($refusedFor($advanced))->toBe([RuleCode::Requisites]);

    // Timetable conflict, then capacity (SFOP_MAX).
    $first = Section::factory()->published()->inTerm($term)->create();
    $meet($first, '09:00', '10:30');
    expect(app(EnrollStudent::class)->handle($record, $first->refresh(), null)->succeeded())->toBeTrue();
    $overlapping = Section::factory()->published()->inTerm($term)->create();
    $meet($overlapping, '10:00', '11:00');
    expect($refusedFor($overlapping))->toBe([RuleCode::TimeConflict]);
    $full = Section::factory()->published()->inTerm($term)->create(['capacity' => 0]);
    expect($refusedFor($full))->toBe([RuleCode::Capacity]);

    // Already registered for the course (any section).
    $sameCourse = Section::factory()->published()->inTerm($term)->forCourse($first->course)->create(['section_code' => '02']);
    expect($refusedFor($sameCourse))->toBe([RuleCode::DuplicateCourse]);

    // Retake: allowed and marked (legacy SSC_RAPPLY).
    $earlierTerm = Term::factory()->create(['start_date' => '2020-03-02', 'end_date' => '2020-06-20']);
    $retaken = Section::factory()->published()->inTerm($term)->create();
    Enrollment::factory()->inSection(Section::factory()->published()->inTerm($earlierTerm)->forCourse($retaken->course)->create())->completed('D0')->create(['student_record_id' => $record->id]);
    expect(app(EnrollStudent::class)->handle($record, $retaken->refresh(), null)->enrollment->is_retake)->toBeTrue();

    // SRDENY: a hold blocks; SRALLOW: an override lets through.
    app(ManageHolds::class)->place($record, HoldType::Administrative, 'Deny list', $this->staff);
    expect($refusedFor(Section::factory()->published()->inTerm($term)->create()))->toBe([RuleCode::Hold]);

    $other = legacyStudent($this->other, 2016);
    app(ManageHolds::class)->grantOverride($other, $term, RuleCode::Eligibility, 'Allowed by the dean', $this->staff, ['section_id' => $nursingOnly->id]);
    expect(app(EnrollStudent::class)->handle($other, $nursingOnly->refresh(), null)->succeeded())->toBeTrue();
});
