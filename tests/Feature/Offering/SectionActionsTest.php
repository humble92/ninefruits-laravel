<?php

use App\Domain\Offering\Actions\AssignSectionInstructor;
use App\Domain\Offering\Actions\CopySections;
use App\Domain\Offering\Actions\CreateSection;
use App\Domain\Offering\Actions\SaveSectionMeeting;
use App\Domain\Offering\Actions\UpdateSection;
use App\Domain\Offering\Actions\UpdateSyllabus;
use App\Domain\Offering\Enums\InstructorRole;
use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\ScopeType;
use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseVersion;
use App\Models\Facility\Room;
use App\Models\Offering\Section;
use App\Models\Offering\SectionSeatGroup;
use App\Models\Organization\OrgUnit;
use App\Models\Person\Instructor;
use App\Models\Term\Term;
use App\Models\Term\TermSession;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->fall = Term::factory()->published()->between('2026-09-01', '2026-12-20')->create(['code' => '2026-FA']);
    $this->spring = Term::factory()->published()->between('2027-03-02', '2027-06-20')->create(['code' => '2027-SP']);
    $this->course = Course::factory()->withVersion(['effective_start_date' => '2020-03-01', 'effective_end_date' => '2027-02-28', 'credits' => 3])->create();
    CourseVersion::factory()->for($this->course)->between('2027-03-01')->create(['credits' => 4]);
    $this->planner = User::factory()->withPermissions([Permission::SectionsManage])->create(['email' => 'planner@example.test']);
    $this->room = Room::factory()->create(['capacity' => 60]);
    $this->instructor = Instructor::factory()->create();
});

function fallSection(object $test, array $meeting = []): Section
{
    $section = app(CreateSection::class)->handle($test->fall->fullTermSession(), $test->course, ['section_code' => '01', 'capacity' => 40], $test->planner);
    app(AssignSectionInstructor::class)->handle($section, $test->instructor, InstructorRole::Primary, $test->planner);
    app(SaveSectionMeeting::class)->handle($section, ['weekday' => 1, 'start_time' => '09:00', 'end_time' => '10:15', 'room_id' => $test->room->id, ...$meeting], $test->planner);

    return $section->refresh();
}

it('offers the course version in effect when the session starts, within the planner\'s scope', function () {
    $fallSection = fallSection($this);
    $springSection = app(CreateSection::class)->handle($this->spring->fullTermSession(), $this->course, ['section_code' => '01', 'capacity' => 40], $this->planner);

    expect($fallSection->version->credits)->toBe('3.0')
        ->and($springSection->version->credits)->toBe('4.0');

    $otherUnit = User::factory()->withPermissions([Permission::SectionsManage], ScopeType::OrgUnit, OrgUnit::factory()->create()->id)->create();
    expect(fn () => app(CreateSection::class)->handle($this->spring->fullTermSession(), $this->course, ['section_code' => '02', 'capacity' => 40], $otherUnit))
        ->toThrow(AuthorizationException::class);
});

it('moves meetings with the section to another session', function () {
    $section = fallSection($this);
    $second = TermSession::factory()->within($this->fall, '2026-10-26', '2026-12-20', 'B')->create();

    app(UpdateSection::class)->handle($section, ['term_session_id' => $second->id], $this->planner);

    expect($section->meetings()->first()->start_date->toDateString())->toBe('2026-10-26');
});

it('copies a term into the next as drafts, reporting what it could not keep', function () {
    $section = fallSection($this);
    SectionSeatGroup::query()->create(['section_id' => $section->id, 'name' => ['en' => 'Majors'], 'capacity' => 20, 'releases_at' => '2026-08-28 09:00:00']);
    app(UpdateSyllabus::class)->handle($section, 'en', ['overview' => 'Lists and trees'], $this->planner, publish: true);

    // In spring the room is taken at that time by another course.
    $other = Section::factory()->inTerm($this->spring)->create();
    app(SaveSectionMeeting::class)->handle($other, ['weekday' => 1, 'start_time' => '09:00', 'end_time' => '10:15', 'room_id' => $this->room->id], $this->planner);

    $preview = app(CopySections::class)->handle($this->fall, $this->spring, $this->planner, dryRun: true);
    expect($preview->created)->toBe([$section->label()])
        ->and(Section::query()->where('term_id', $this->spring->id)->count())->toBe(1);

    $report = app(CopySections::class)->handle($this->fall, $this->spring, $this->planner);
    $copy = Section::query()->where('term_id', $this->spring->id)->where('course_id', $this->course->id)->sole();

    expect($copy->status)->toBe(SectionStatus::Draft)
        ->and($copy->version->credits)->toBe('4.0')
        ->and($copy->instructors()->pluck('instructors.id')->all())->toBe([$this->instructor->id])
        ->and($copy->meetings()->first()->room_id)->toBeNull()
        ->and($report->notes[$section->label()])->toHaveCount(1)
        ->and($copy->seatGroups()->first()->releases_at->toDateString())->toBe('2027-02-26') // shifted by the 182 days between the term starts
        ->and($copy->syllabus->published_at)->toBeNull();

    expect(app(CopySections::class)->handle($this->fall, $this->spring, $this->planner)->skipped)->toHaveKey($section->label());
});

it('lets the section\'s instructors and planners write the syllabus, nobody else', function () {
    $section = fallSection($this);
    $teacher = User::factory()->create(['person_id' => $this->instructor->person_id]);

    app(UpdateSyllabus::class)->handle($section, 'ko', ['overview' => '개요', 'weekly_plan' => [['week' => 1, 'topic' => '소개']]], $teacher, publish: true);

    expect($section->syllabus->published_at)->not->toBeNull()
        ->and(fn () => app(UpdateSyllabus::class)->handle($section, 'ko', [], User::factory()->create()))->toThrow(AuthorizationException::class)
        ->and(fn () => app(UpdateSyllabus::class)->handle($section, 'ko', ['weekly_plan' => [['week' => 99, 'topic' => 'x']]], $teacher))->toThrow(ValidationException::class);
});

it('copies and checks terms from the command line', function () {
    $section = fallSection($this);
    Room::query()->whereKey($this->room->id)->update(['capacity' => 20]);

    $this->artisan('schedule:check-conflicts', ['term' => '2026-FA'])->expectsOutputToContain('holds 20')->assertExitCode(2);

    $this->artisan('section:copy', ['from-term' => '2026-FA', 'to-term' => '2027-SP'])->assertFailed();
    $this->artisan('section:copy', ['from-term' => '2026-FA', 'to-term' => '2027-SP', '--actor' => 'planner@example.test', '--dry-run' => true])
        ->expectsOutputToContain('Dry run: 1 section(s) copied')->assertSuccessful();
    expect(Section::query()->where('term_id', $this->spring->id)->exists())->toBeFalse();

    $this->artisan('section:copy', ['from-term' => '2026-FA', 'to-term' => '2027-SP', '--actor' => 'planner@example.test'])->assertSuccessful();
    expect(Section::query()->where('term_id', $this->spring->id)->count())->toBe(1);
});
