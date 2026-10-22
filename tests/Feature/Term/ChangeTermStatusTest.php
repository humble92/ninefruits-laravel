<?php

use App\Domain\Term\Actions\ChangeTermStatus;
use App\Domain\Term\Enums\TermStatus;
use App\Models\Security\Activity;
use App\Models\Term\Term;
use Illuminate\Validation\ValidationException;

it('moves a term forward and records it', function () {
    $term = Term::factory()->create();

    app(ChangeTermStatus::class)->handle($term, TermStatus::Published, 'Timetable ready');

    $activity = Activity::query()->where('event', 'term.status_changed')->sole();

    expect($term->fresh()->status)->toBe(TermStatus::Published)
        ->and($activity->reason)->toBe('Timetable ready')
        ->and($activity->properties['from'])->toBe('planned');
});

it('rejects transitions that skip steps', function () {
    app(ChangeTermStatus::class)->handle(Term::factory()->create(), TermStatus::Archived);
})->throws(ValidationException::class);

it('keeps archived terms archived', function () {
    expect(TermStatus::Archived->allowedNext())->toBe([]);
});
