<?php

use App\Domain\Curriculum\Enums\RequisiteKind;
use App\Domain\Offering\Actions\SaveSectionMeeting;
use App\Domain\Registration\Actions\DropEnrollment;
use App\Domain\Registration\Actions\EnrollStudent;
use App\Domain\Registration\Actions\ManageHolds;
use App\Domain\Registration\Data\EnrollOptions;
use App\Domain\Registration\Enums\AttemptOutcome;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Registration\Enums\HoldType;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\WaitlistStatus;
use App\Domain\Registration\Notifications\WaitlistOutcomeNotification;
use App\Domain\Security\Enums\Permission;
use App\Domain\Term\Enums\TermWindowType;
use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseEquivalency;
use App\Models\Curriculum\CourseRequisite;
use App\Models\Facility\Room;
use App\Models\Offering\Section;
use App\Models\Offering\SectionSeatGroup;
use App\Models\Organization\Program;
use App\Models\Person\StudentRecord;
use App\Models\Registration\CreditLimitPolicy;
use App\Models\Registration\Enrollment;
use App\Models\Registration\RegistrationAttempt;
use App\Models\Registration\WaitlistEntry;
use App\Models\Term\Term;
use App\Models\Term\TermWindow;
use App\Models\User;
use App\Support\Targeting\TargetCriteria;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Notification::fake();
    $this->term = Term::factory()->published()->between(now()->addMonth()->toDateString(), now()->addMonths(5)->toDateString())->create();
    TermWindow::factory()->for($this->term)->ofType(TermWindowType::Registration)->between(now()->subDay(), now()->addWeek())->create();
    TermWindow::factory()->for($this->term)->ofType(TermWindowType::AddDrop)->between(now()->subDay(), now()->addWeek())->create();
    $this->program = Program::factory()->create();
    $this->staff = User::factory()->withPermissions([Permission::RegistrationManage, Permission::RegistrationHoldsManage])->create();
    $this->planner = User::factory()->withPermissions([Permission::SectionsManage])->create();
    CreditLimitPolicy::query()->create(['name' => ['en' => 'Standard'], 'max_credits' => 9]);
});

function registrationStudent(object $test, array $attributes = []): array
{
    $record = StudentRecord::factory()->inProgram($test->program)->create($attributes);

    return [$record, User::factory()->create(['person_id' => $record->person_id])];
}

function registrationSection(object $test, int $capacity = 30, array $meeting = ['weekday' => 1, 'start_time' => '09:00', 'end_time' => '10:15'], array $attributes = []): Section
{
    $section = Section::factory()->published()->inTerm($test->term)->create(['capacity' => $capacity, ...$attributes]);
    app(SaveSectionMeeting::class)->handle($section, [...$meeting, 'room_id' => Room::factory()->create()->id], $test->planner);

    return $section->refresh();
}

function registrationEnroll(StudentRecord $record, Section $section, ?User $actor, ?EnrollOptions $options = null)
{
    return app(EnrollStudent::class)->handle($record, $section->refresh(), $actor, $options);
}

it('enrolls with a snapshot of the course, counts the seat and logs the attempt', function () {
    [$record, $user] = registrationStudent($this);
    $section = registrationSection($this);

    $outcome = registrationEnroll($record, $section, $user);

    expect($outcome->outcome)->toBe(AttemptOutcome::Succeeded)
        ->and($outcome->enrollment->credits)->toBe($section->version->credits)
        ->and($outcome->enrollment->course_version_id)->toBe($section->course_version_id)
        ->and($section->refresh()->enrolled_count)->toBe(1)
        ->and(RegistrationAttempt::query()->where('student_record_id', $record->id)->sole()->outcome)->toBe(AttemptOutcome::Succeeded);
});

it('reports every reason at once when refusing', function () {
    [$record, $user] = registrationStudent($this);
    app(ManageHolds::class)->place($record, HoldType::Unregistered, 'Fee unpaid', $this->staff);
    $first = registrationSection($this);
    Enrollment::factory()->inSection($first)->create(['student_record_id' => $record->id, 'credits' => 8]);
    $prerequisite = Course::factory()->create();
    $second = registrationSection($this, meeting: ['weekday' => 1, 'start_time' => '10:00', 'end_time' => '11:00']);
    CourseRequisite::query()->create(['course_version_id' => $second->course_version_id, 'kind' => RequisiteKind::Prerequisite, 'required_course_id' => $prerequisite->id]);

    $outcome = registrationEnroll($record, $second, $user);
    $failed = collect($outcome->evaluation->results)->filter->blocks()->map(fn ($result) => $result->code)->values()->all();

    expect($outcome->outcome)->toBe(AttemptOutcome::Refused)
        ->and($failed)->toEqualCanonicalizing([RuleCode::Hold, RuleCode::TimeConflict, RuleCode::CreditLimit, RuleCode::Requisites])
        ->and($second->refresh()->enrolled_count)->toBe(0);
});

it('counts an equivalent course as the prerequisite', function () {
    [$record, $user] = registrationStudent($this);
    $old = Course::factory()->create();
    $new = Course::factory()->create();
    CourseEquivalency::query()->create(['course_id' => $new->id, 'equivalent_course_id' => $old->id, 'effective_start_date' => '2000-01-01']);
    $earlier = Section::factory()->published()->forCourse($old)->create();
    Enrollment::factory()->inSection($earlier)->completed('B0')->create(['student_record_id' => $record->id]);
    $section = registrationSection($this);
    CourseRequisite::query()->create(['course_version_id' => $section->course_version_id, 'kind' => RequisiteKind::Prerequisite, 'required_course_id' => $new->id, 'minimum_grade' => null]);

    expect(registrationEnroll($record, $section, $user)->succeeded())->toBeTrue();
});

it('keeps reserved seats for their students until released', function () {
    $section = registrationSection($this, capacity: 2);
    SectionSeatGroup::query()->create(['section_id' => $section->id, 'name' => ['en' => 'Majors'], 'capacity' => 1, 'eligibility' => new TargetCriteria(programIds: [$this->program->id]), 'releases_at' => now()->addDay()]);
    $other = Program::factory()->create();

    [$outsider1, $user1] = registrationStudent($this, ['program_id' => $other->id, 'org_unit_id' => $other->org_unit_id]);
    [$outsider2, $user2] = registrationStudent($this, ['program_id' => $other->id, 'org_unit_id' => $other->org_unit_id]);
    [$major, $majorUser] = registrationStudent($this);

    expect(registrationEnroll($outsider1, $section, $user1)->succeeded())->toBeTrue()
        ->and(registrationEnroll($outsider2, $section, $user2)->refusedForSeat())->toBeTrue()
        ->and(registrationEnroll($major, $section, $majorUser)->enrollment?->seat_group_id)->not->toBeNull();
});

it('promotes the waitlist in order, checking every rule again', function () {
    $section = registrationSection($this, capacity: 1);
    $section->update(['waitlist_capacity' => 5]);
    [$holder, $holderUser] = registrationStudent($this);
    [$conflicted, $conflictedUser] = registrationStudent($this);
    [$next, $nextUser] = registrationStudent($this);

    registrationEnroll($holder, $section, $holderUser);
    expect(registrationEnroll($conflicted, $section, $conflictedUser, new EnrollOptions(waitlistIfFull: true))->outcome)->toBe(AttemptOutcome::Waitlisted);
    $this->travel(1)->seconds();
    expect(registrationEnroll($next, $section, $nextUser, new EnrollOptions(waitlistIfFull: true))->waitlistEntry->position())->toBe(2);

    // The first in line registers for another section at the same time meanwhile.
    registrationEnroll($conflicted, registrationSection($this, meeting: ['weekday' => 1, 'start_time' => '09:30', 'end_time' => '10:30']), $conflictedUser);

    app(DropEnrollment::class)->drop(Enrollment::query()->where('student_record_id', $holder->id)->sole(), $holderUser);

    expect(WaitlistEntry::query()->where('student_record_id', $conflicted->id)->sole()->status)->toBe(WaitlistStatus::Removed)
        ->and(Enrollment::query()->where('student_record_id', $next->id)->where('section_id', $section->id)->sole()->status)->toBe(EnrollmentStatus::Enrolled)
        ->and($section->refresh()->enrolled_count)->toBe(1);
    Notification::assertSentTo($nextUser, WaitlistOutcomeNotification::class);
    Notification::assertSentTo($conflictedUser, WaitlistOutcomeNotification::class);
});

it('lets staff waive a rule with a reason, and honours overrides', function () {
    [$record, $user] = registrationStudent($this);
    $restricted = registrationSection($this, attributes: ['eligibility' => new TargetCriteria(yearLevels: [4])]);

    expect(registrationEnroll($record, $restricted, $user)->outcome)->toBe(AttemptOutcome::Refused)
        ->and(fn () => registrationEnroll($record, $restricted, $this->staff, new EnrollOptions(waive: [RuleCode::Eligibility])))->toThrow(ValidationException::class)
        ->and(registrationEnroll($record, $restricted, $this->staff, new EnrollOptions(waive: [RuleCode::Eligibility], reason: 'Approved by the department'))->succeeded())->toBeTrue();

    [$other, $otherUser] = registrationStudent($this);
    app(ManageHolds::class)->grantOverride($other, $this->term, RuleCode::Eligibility, 'Exchange student', $this->staff, ['section_id' => $restricted->id]);
    expect(registrationEnroll($other, $restricted, $otherUser)->succeeded())->toBeTrue();
});

it('drops only while registration or add/drop is open, unless staff do it', function () {
    [$record, $user] = registrationStudent($this);
    $section = registrationSection($this);
    $enrollment = registrationEnroll($record, $section, $user)->enrollment;
    TermWindow::query()->where('term_id', $this->term->id)->update(['closes_at' => now()->subMinute()]);

    expect(fn () => app(DropEnrollment::class)->drop($enrollment, $user))->toThrow(ValidationException::class);

    app(DropEnrollment::class)->drop($enrollment, $this->staff, 'Medical reasons');
    expect($enrollment->refresh()->status)->toBe(EnrollmentStatus::Dropped)
        ->and($section->refresh()->enrolled_count)->toBe(0);
});
