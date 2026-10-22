<?php

use App\Domain\Institution\Settings\InstitutionSettings;
use App\Domain\Term\Enums\TermStatus;
use App\Domain\Term\Enums\TermWindowType;
use App\Domain\Term\Services\TermResolver;
use App\Models\Term\AcademicYear;
use App\Models\Term\Term;
use App\Models\Term\TermType;
use App\Models\Term\TermWindow;
use App\Support\Targeting\TargetCriteria;
use App\Support\Targeting\TargetSubject;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;

function student(int $yearLevel): TargetSubject
{
    return new readonly class($yearLevel) implements TargetSubject
    {
        public function __construct(private int $yearLevel) {}

        public function targetProgramIds(): array
        {
            return [];
        }

        public function targetOrgUnitIds(): array
        {
            return [];
        }

        public function targetAdmissionYear(): ?int
        {
            return 2024;
        }

        public function targetYearLevel(): ?int
        {
            return $this->yearLevel;
        }

        public function targetStudentStatus(): ?string
        {
            return 'enrolled';
        }

        public function targetAcademicStanding(): ?string
        {
            return null;
        }

        public function targetTags(): array
        {
            return [];
        }
    };
}

/**
 * Spring 2026 (grading), fall 2026 (in session), spring 2027 (registration, staggered).
 */
beforeEach(function () {
    $spring = TermType::factory()->create(['code' => 'spring']);
    $fall = TermType::factory()->create(['code' => 'fall']);
    $y2026 = AcademicYear::factory()->forYear(2026)->create();
    $y2027 = AcademicYear::factory()->forYear(2027)->create();

    $make = fn (AcademicYear $year, TermType $type, string $code, string $start, string $end, TermStatus $status) => tap(
        Term::factory()->create(['academic_year_id' => $year->id, 'term_type_id' => $type->id, 'code' => $code, 'start_date' => $start, 'end_date' => $end]),
        fn (Term $term) => $term->forceFill(['status' => $status])->save(),
    );

    $this->past = $make($y2026, $spring, '2026-SP', '2026-03-02', '2026-06-20', TermStatus::GradesClosed);
    $this->current = $make($y2026, $fall, '2026-FA', '2026-09-01', '2026-12-20', TermStatus::Published);
    $this->next = $make($y2027, $spring, '2027-SP', '2027-03-02', '2027-06-20', TermStatus::Published);

    $this->at = CarbonImmutable::parse('2026-11-10 10:00', 'Asia/Seoul');

    TermWindow::factory()->for($this->past)->ofType(TermWindowType::GradeAppeal)->between($this->at->subDay(), $this->at->addDay())->create();
    TermWindow::factory()->for($this->current)->ofType(TermWindowType::GradeEntry)->between($this->at->subDays(2), $this->at->addDays(5))->create();
    TermWindow::factory()->for($this->next)->ofType(TermWindowType::Registration)->between($this->at->subHour(), $this->at->addDays(3))
        ->eligibleFor(new TargetCriteria(yearLevels: [4]))->create();
    TermWindow::factory()->for($this->next)->ofType(TermWindowType::Registration)->between($this->at->addDay(), $this->at->addDays(3))
        ->eligibleFor(new TargetCriteria(yearLevels: [1, 2, 3]))->create();
});

it('answers each business question with its own term at the same time', function () {
    $resolver = app(TermResolver::class);

    expect($resolver->inSession($this->at)->pluck('code')->all())->toBe(['2026-FA'])
        ->and($resolver->termsOpenFor(TermWindowType::GradeEntry, at: $this->at)->pluck('code')->all())->toBe(['2026-FA'])
        ->and($resolver->termsOpenFor(TermWindowType::GradeAppeal, at: $this->at)->pluck('code')->all())->toBe(['2026-SP'])
        ->and($resolver->termsOpenFor(TermWindowType::Registration, at: $this->at)->pluck('code')->all())->toBe(['2027-SP']);
});

it('opens registration in stages by eligibility', function () {
    $resolver = app(TermResolver::class);

    expect($resolver->termsOpenFor(TermWindowType::Registration, student(4), $this->at)->pluck('code')->all())->toBe(['2027-SP'])
        ->and($resolver->termsOpenFor(TermWindowType::Registration, student(2), $this->at))->toBeEmpty()
        ->and($resolver->nextWindow(TermWindowType::Registration, student(2), $this->at)?->opens_at->equalTo($this->at->addDay()))->toBeTrue()
        ->and($resolver->termsOpenFor(TermWindowType::Registration, student(2), $this->at->addDays(2))->pluck('code')->all())->toBe(['2027-SP']);
});

it('never opens windows of planned or archived terms', function () {
    $this->next->forceFill(['status' => TermStatus::Planned])->save();

    expect(app(TermResolver::class)->openWindows(TermWindowType::Registration, $this->at))->toBeEmpty();
});

it('decides the term in session by the institution date', function () {
    $settings = app(InstitutionSettings::class);
    $settings->timezone = 'Asia/Seoul';
    $settings->save();

    // 2026-08-31 16:00 UTC is already 2026-09-01 in Seoul.
    $moment = CarbonImmutable::parse('2026-08-31 16:00', 'UTC');

    expect(app(TermResolver::class)->inSession($moment)->pluck('code')->all())->toBe(['2026-FA']);
});

it('shows the open windows with term:inspect', function () {
    $settings = app(InstitutionSettings::class);
    $settings->timezone = 'Asia/Seoul';
    $settings->save();

    expect(Artisan::call('term:inspect', ['--at' => $this->at->toIso8601String()]))->toBe(0);

    $output = Artisan::output();

    expect($output)->toContain('In session')->toContain('2026-FA')
        ->toContain('grade_entry')
        ->toContain('2026-11-15 10:00 Asia/Seoul');
});
