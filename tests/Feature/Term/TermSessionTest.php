<?php

use App\Domain\Term\Actions\CopySessionWindows;
use App\Domain\Term\Enums\TermWindowType;
use App\Domain\Term\Services\TermResolver;
use App\Models\Term\Term;
use App\Models\Term\TermSession;
use App\Models\Term\TermWindow;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->term = Term::factory()->published()->between('2026-09-01', '2026-12-20')->create();
});

it('gives every term a full-term session that follows the term dates', function () {
    $full = $this->term->fullTermSession();

    expect($full->code)->toBe('FULL')
        ->and($full->getTranslations('name'))->toHaveKeys(['en', 'ko', 'zh_CN'])
        ->and($full->start_date->toDateString())->toBe('2026-09-01');

    $this->term->update(['end_date' => '2026-12-22']);
    expect($full->refresh()->end_date->toDateString())->toBe('2026-12-22');
});

it('keeps sessions inside their term', function () {
    expect(fn () => DB::transaction(fn () => TermSession::factory()->within($this->term, '2026-08-20', '2026-10-01')->create()))
        ->toThrow(QueryException::class, 'must lie within its term');

    TermSession::factory()->within($this->term, '2026-11-01', '2026-11-21', 'I')->create();
    expect(fn () => $this->term->update(['end_date' => '2026-11-15']))->toThrow(ValidationException::class);
});

it('puts course windows on a session (the full term unless chosen) and term windows on none', function () {
    $dropDeadline = TermWindow::factory()->for($this->term)->ofType(TermWindowType::Withdrawal)->create();
    $registration = TermWindow::factory()->for($this->term)->ofType(TermWindowType::Registration)->create();

    expect($dropDeadline->term_session_id)->toBe($this->term->fullTermSession()->id)
        ->and($registration->term_session_id)->toBeNull();

    $second = TermSession::factory()->within($this->term, '2026-10-26', '2026-12-20', 'B')->create();
    expect(fn () => DB::transaction(fn () => DB::table('term_windows')->where('id', $registration->id)->update(['term_session_id' => $second->id])))
        ->toThrow(QueryException::class, 'term_windows_session_check');
});

it('answers per session whether a course window is open', function () {
    $at = CarbonImmutable::parse('2026-11-02 10:00', 'Asia/Seoul');
    $second = TermSession::factory()->within($this->term, '2026-10-26', '2026-12-20', 'B')->create();
    TermWindow::factory()->for($this->term)->ofType(TermWindowType::AddDrop)->between($at->subDays(60), $at->subDays(55))->create();
    TermWindow::factory()->for($this->term)->ofType(TermWindowType::AddDrop)->between($at->subDay(), $at->addDay())->create(['term_session_id' => $second->id]);

    $resolver = app(TermResolver::class);
    expect($resolver->isOpenInSession(TermWindowType::AddDrop, $second, at: $at))->toBeTrue()
        ->and($resolver->isOpenInSession(TermWindowType::AddDrop, $this->term->fullTermSession(), at: $at))->toBeFalse();
});

it('copies the full-term windows to a session, anchored to its start or end', function () {
    $full = $this->term->fullTermSession();
    TermWindow::factory()->for($this->term)->ofType(TermWindowType::AddDrop)->between(CarbonImmutable::parse('2026-09-01 09:00'), CarbonImmutable::parse('2026-09-05 18:00'))->create();
    TermWindow::factory()->for($this->term)->ofType(TermWindowType::GradeEntry)->between(CarbonImmutable::parse('2026-12-14 09:00'), CarbonImmutable::parse('2026-12-24 18:00'))->create();
    $second = TermSession::factory()->within($this->term, '2026-10-26', '2026-12-10', 'B')->create();

    expect(app(CopySessionWindows::class)->handle($full, $second, User::factory()->create()))->toBe(2);

    $windows = $second->windows()->get()->keyBy(fn (TermWindow $window) => $window->window_type->value);
    expect($windows['add_drop']->opens_at->toDateString())->toBe('2026-10-26') // +55 days from the start
        ->and($windows['grade_entry']->closes_at->toDateString())->toBe('2026-12-14'); // -10 days from the end
});
