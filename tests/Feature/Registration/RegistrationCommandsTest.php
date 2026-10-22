<?php

use App\Domain\Registration\Actions\EnrollStudent;
use App\Domain\Registration\Enums\WaitlistStatus;
use App\Domain\Registration\Jobs\PromoteWaitlistJob;
use App\Domain\Term\Enums\TermWindowType;
use App\Models\Offering\Section;
use App\Models\Offering\SectionSeatGroup;
use App\Models\Person\StudentRecord;
use App\Models\Registration\CreditLimitPolicy;
use App\Models\Registration\RegistrationAttempt;
use App\Models\Registration\WaitlistEntry;
use App\Models\Term\Term;
use App\Models\Term\TermWindow;
use App\Models\User;
use App\Support\Targeting\TargetCriteria;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Notification::fake();
    $this->term = Term::factory()->published()->between(now()->addMonth()->toDateString(), now()->addMonths(5)->toDateString())->create();
    $this->section = Section::factory()->published()->inTerm($this->term)->create(['capacity' => 1, 'waitlist_capacity' => 2]);
    $this->section->load('course');
    $this->record = StudentRecord::factory()->create();
});

function openRegistration(Term $term): void
{
    TermWindow::factory()->for($term)->ofType(TermWindowType::Registration)->between(now()->subDay(), now()->addWeek())->create();
    TermWindow::factory()->for($term)->ofType(TermWindowType::AddDrop)->between(now()->subDay(), now()->addWeek())->create();
}

it('reports missing windows and credit limits before registration opens', function () {
    $this->artisan('registration:preflight', ['term' => $this->term->code])
        ->expectsOutputToContain('No registration window.')
        ->expectsOutputToContain('match no credit limit policy')
        ->assertExitCode(2);

    openRegistration($this->term);
    CreditLimitPolicy::query()->create(['name' => ['en' => 'Standard'], 'max_credits' => 18, 'eligibility' => TargetCriteria::everyone()]);

    $this->artisan('registration:preflight', ['term' => $this->term->code])->expectsOutputToContain('No rule set')->assertSuccessful();
    $this->artisan('registration:preflight', ['term' => 'NOPE'])->assertFailed();
});

it('simulates a registration without changing anything', function () {
    $label = $this->section->label();

    $this->artisan('registration:simulate', ['student-number' => $this->record->student_number, 'section' => $label])
        ->expectsOutputToContain(__('registration.rules.window.failed.enroll'))
        ->assertExitCode(2);

    openRegistration($this->term);

    $this->artisan('registration:simulate', ['student-number' => $this->record->student_number, 'section' => $label])
        ->expectsOutputToContain('Would be registered.')
        ->assertSuccessful();

    expect($this->section->refresh()->enrolled_count)->toBe(0)
        ->and(RegistrationAttempt::query()->count())->toBe(0);
});

it('promotes waiting students when a seat is free', function () {
    openRegistration($this->term);
    $first = StudentRecord::factory()->create();
    app(EnrollStudent::class)->handle($first, $this->section, null);
    $entry = WaitlistEntry::query()->create(['section_id' => $this->section->id, 'student_record_id' => $this->record->id, 'requested_at' => now()]);

    $this->section->forceFill(['capacity' => 2])->save();

    $this->artisan('waitlist:promote', ['term' => $this->term->code, '--actor' => User::factory()->create()->email])
        ->expectsOutputToContain('1 section(s): 1 promoted, 0 removed, 0 expired.')
        ->assertSuccessful();

    expect($entry->refresh()->status)->toBe(WaitlistStatus::Promoted);
});

it('hands released reserved seats to the waitlist once', function () {
    Queue::fake();
    SectionSeatGroup::query()->create(['section_id' => $this->section->id, 'name' => ['en' => 'Majors'], 'capacity' => 1, 'releases_at' => now()->subMinute()]);

    $this->artisan('registration:release-reserved-seats')->expectsOutputToContain('1 section(s)')->assertSuccessful();
    $this->artisan('registration:release-reserved-seats')->expectsOutputToContain('0 section(s)')->assertSuccessful();

    Queue::assertPushed(PromoteWaitlistJob::class, 1);
});
