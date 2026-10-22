<?php

use App\Models\Organization\OrgUnit;
use App\Models\Term\TermWindow;
use Carbon\CarbonImmutable;

it('stores and compares times in other time zones as the same instant', function () {
    $seoul = CarbonImmutable::parse('2026-11-10 10:00', 'Asia/Seoul');
    $window = TermWindow::factory()->between($seoul, $seoul->addHour())->create();

    expect($window->fresh()->opens_at->equalTo(CarbonImmutable::parse('2026-11-10 01:00', 'UTC')))->toBeTrue()
        ->and(TermWindow::query()->where('opens_at', '<=', $seoul)->whereKey($window->id)->exists())->toBeTrue()
        ->and(TermWindow::query()->where('opens_at', '<', $seoul)->whereKey($window->id)->exists())->toBeFalse();
});

it('keeps the local date for date columns', function () {
    $unit = OrgUnit::factory()->create(['effective_start_date' => CarbonImmutable::parse('2026-03-02 00:00', 'Asia/Seoul')]);

    expect($unit->fresh()->effective_start_date->toDateString())->toBe('2026-03-02');
});
