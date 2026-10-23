<?php

use App\Domain\Grading\Notifications\MissingGradesNotification;
use App\Domain\Term\Enums\TermStatus;
use App\Models\Grading\GradeScale;
use App\Models\Grading\TermRecord;
use App\Models\Offering\Section;
use App\Models\Offering\SectionInstructor;
use App\Models\Person\Instructor;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    GradeScale::factory()->korean()->create();
    $this->term = Term::factory()->published()->between('2025-03-01', '2025-06-20')->create();
    $this->section = Section::factory()->published()->inTerm($this->term)->create();
    $this->finished = Enrollment::factory()->inSection(Section::factory()->published()->inTerm($this->term)->create())->completed('A0')->create();
});

it('closes a term only when every grade is final', function () {
    $open = Enrollment::factory()->inSection($this->section)->create();

    $this->artisan('term:close', ['term' => 'NOPE'])->assertFailed();
    $this->artisan('term:close', ['term' => $this->term->code])
        ->expectsOutputToContain($this->section->load('course')->label().': 1 grade(s) not finalized.')
        ->assertExitCode(2);

    DB::table('enrollments')->where('id', $open->id)->update(['status' => 'withdrawn']);

    $this->artisan('term:close', ['term' => $this->term->code, '--dry-run' => true])->expectsOutputToContain('Ready to close.')->assertSuccessful();
    expect($this->term->refresh()->status)->toBe(TermStatus::Published);

    $this->artisan('term:close', ['term' => $this->term->code, '--reason' => 'End of spring'])->assertSuccessful();
    expect($this->term->refresh()->status)->toBe(TermStatus::GradesClosed)
        ->and(TermRecord::query()->where('term_id', $this->term->id)->sole())->term_gpa->toBe('4.00')->closed_at->not->toBeNull();
});

it('recalculates term records with a reason', function () {
    $this->artisan('grades:recalculate', ['--term' => $this->term->code])->assertFailed();
    $this->artisan('grades:recalculate', ['--term' => $this->term->code, '--reason' => 'Policy change'])
        ->expectsOutputToContain('1 student(s) recalculated.')->assertSuccessful();

    expect(TermRecord::query()->where('student_record_id', $this->finished->student_record_id)->sole()->cumulative_gpa)->toBe('4.00');
});

it('reminds instructors of grades not submitted', function () {
    Enrollment::factory()->inSection($this->section)->create();
    $instructor = Instructor::factory()->create();
    SectionInstructor::query()->create(['section_id' => $this->section->id, 'instructor_id' => $instructor->id, 'role' => 'primary']);
    $user = User::factory()->create(['person_id' => $instructor->person_id]);

    $this->artisan('grades:remind-missing', ['term' => $this->term->code, '--dry-run' => true])->expectsOutputToContain('0 reminder(s) sent.')->assertSuccessful();
    Notification::assertNothingSent();

    $this->artisan('grades:remind-missing', ['term' => $this->term->code])->expectsOutputToContain('1 reminder(s) sent.')->assertSuccessful();
    Notification::assertSentTo($user, MissingGradesNotification::class);
});
