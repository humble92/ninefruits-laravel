<?php

/*
| Phase 4 completion criterion: a room or an instructor cannot be booked twice at the same
| time, enforced by the database (exclusion constraint, triggers) and explained by the
| application.
*/

use App\Domain\Offering\Actions\AssignSectionInstructor;
use App\Domain\Offering\Actions\ChangeSectionStatus;
use App\Domain\Offering\Actions\SaveSectionMeeting;
use App\Domain\Offering\Enums\InstructorRole;
use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Security\Enums\Permission;
use App\Models\Facility\Room;
use App\Models\Offering\Section;
use App\Models\Person\Instructor;
use App\Models\Term\Term;
use App\Models\Term\TermSession;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->term = Term::factory()->published()->between('2026-09-01', '2026-12-20')->create();
    $this->firstHalf = TermSession::factory()->within($this->term, '2026-09-01', '2026-10-25', 'A')->create();
    $this->secondHalf = TermSession::factory()->within($this->term, '2026-10-26', '2026-12-20', 'B')->create();
    $this->room = Room::factory()->create(['capacity' => 50]);
    $this->instructor = Instructor::factory()->create();
    $this->staff = User::factory()->withPermissions([Permission::SectionsManage])->create();
    $this->section = fn (?TermSession $session = null) => Section::factory()->inTerm($this->term)
        ->create(['term_session_id' => ($session ?? $this->term->fullTermSession())->id]);
    $this->meet = fn (Section $section, string $start, string $end, ?Room $room = null, int $weekday = 1) => app(SaveSectionMeeting::class)
        ->handle($section, ['weekday' => $weekday, 'start_time' => $start, 'end_time' => $end, 'room_id' => ($room ?? $this->room)->id], $this->staff);
});

function conflictMessage(Closure $callback): ?string
{
    try {
        $callback();
    } catch (ValidationException $exception) {
        return collect($exception->errors())->flatten()->first();
    }

    return null;
}

it('refuses a room booked at an overlapping time and names the other section', function () {
    $first = ($this->section)();
    ($this->meet)($first, '09:00', '10:15');

    $second = ($this->section)();
    $message = conflictMessage(fn () => ($this->meet)($second, '10:00', '11:15'));

    expect($message)->toContain($first->label())
        ->and(conflictMessage(fn () => ($this->meet)($second, '10:15', '11:30')))->toBeNull() // back to back is fine
        ->and(conflictMessage(fn () => ($this->meet)(($this->section)(), '09:30', '10:00', weekday: 2)))->toBeNull(); // other day
});

it('lets the halves of a term share a room at the same time', function () {
    ($this->meet)(($this->section)($this->firstHalf), '09:00', '10:15');

    expect(conflictMessage(fn () => ($this->meet)(($this->section)($this->secondHalf), '09:00', '10:15')))->toBeNull()
        ->and(conflictMessage(fn () => ($this->meet)(($this->section)(), '09:00', '10:15')))->not->toBeNull();
});

it('refuses an instructor at two places, whichever is assigned first', function () {
    $assign = fn (Section $section) => app(AssignSectionInstructor::class)->handle($section, $this->instructor, InstructorRole::Primary, $this->staff);
    $otherRoom = Room::factory()->create();

    $first = ($this->section)();
    ($this->meet)($first, '13:00', '15:00');
    $assign($first);

    // Instructor first, then a meeting at the same time.
    $second = ($this->section)();
    $assign($second);
    expect(conflictMessage(fn () => ($this->meet)($second, '14:00', '16:00', $otherRoom)))->toContain($first->label());

    // Meeting first, then the instructor.
    $third = ($this->section)();
    ($this->meet)($third, '14:30', '15:30', Room::factory()->create());
    expect(conflictMessage(fn () => $assign($third)))->toContain($first->label());
});

it('frees rooms and instructors of a cancelled section, and checks them again on reopening', function () {
    $first = ($this->section)();
    ($this->meet)($first, '09:00', '10:15');
    app(ChangeSectionStatus::class)->handle($first, SectionStatus::Cancelled, $this->staff, 'Too few students');

    $second = ($this->section)();
    ($this->meet)($second, '09:00', '10:15');

    expect(conflictMessage(fn () => app(ChangeSectionStatus::class)->handle($first->refresh(), SectionStatus::Draft, $this->staff)))->toContain($second->label());
});

it('enforces the rules even for writes that bypass the application', function () {
    $first = ($this->section)();
    ($this->meet)($first, '09:00', '10:15');
    $second = ($this->section)();

    expect(fn () => DB::transaction(fn () => DB::table('section_meetings')->insert([
        'section_id' => $second->id, 'weekday' => 1, 'start_time' => '09:30', 'end_time' => '10:00',
        'room_id' => $this->room->id, 'start_date' => '2026-09-01', 'end_date' => '2026-12-20',
    ])))->toThrow(QueryException::class, 'section_meetings_room_excl');
});
