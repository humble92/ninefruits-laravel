<?php

use App\Domain\Organization\Actions\ReorganizeOrgUnit;
use App\Domain\Organization\Enums\OrgUnitChangeType;
use App\Models\Organization\OrgUnit;
use App\Models\Security\Activity;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

it('ends the unit the day before and links the successor', function () {
    $old = OrgUnit::factory()->create(['effective_start_date' => '2000-03-01']);
    $new = OrgUnit::factory()->create(['effective_start_date' => '2025-03-01']);

    app(ReorganizeOrgUnit::class)->handle($old, OrgUnitChangeType::Merged, [$new], CarbonImmutable::parse('2025-03-01'), 'Merger');

    $succession = $old->successions()->sole();

    expect($old->fresh()->effective_end_date->toDateString())->toBe('2025-02-28')
        ->and($succession->successor_org_unit_id)->toBe($new->id)
        ->and($succession->change_type)->toBe(OrgUnitChangeType::Merged)
        ->and(OrgUnit::query()->effectiveOn('2025-03-01')->whereKey($old->id)->exists())->toBeFalse()
        ->and(Activity::query()->where('event', 'org_unit.reorganized')->sole()->reason)->toBe('Merger');
});

it('allows several successors only for a split', function () {
    $old = OrgUnit::factory()->create();

    app(ReorganizeOrgUnit::class)->handle($old, OrgUnitChangeType::Merged, [OrgUnit::factory()->create(), OrgUnit::factory()->create()], CarbonImmutable::parse('2025-03-01'), 'x');
})->throws(ValidationException::class);

it('records a split into several units', function () {
    $old = OrgUnit::factory()->create();
    [$a, $b] = [OrgUnit::factory()->create(), OrgUnit::factory()->create()];

    app(ReorganizeOrgUnit::class)->handle($old, OrgUnitChangeType::Split, [$a, $b], CarbonImmutable::parse('2025-03-01'), 'Split');

    expect($old->successions()->pluck('successor_org_unit_id')->all())->toEqualCanonicalizing([$a->id, $b->id]);
});

it('rejects an effective date before the unit started', function () {
    $old = OrgUnit::factory()->create(['effective_start_date' => '2020-03-01']);

    app(ReorganizeOrgUnit::class)->handle($old, OrgUnitChangeType::Renamed, [OrgUnit::factory()->create()], CarbonImmutable::parse('2019-01-01'), 'x');
})->throws(ValidationException::class);
