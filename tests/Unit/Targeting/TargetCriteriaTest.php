<?php

use App\Support\Targeting\TargetCriteria;
use App\Support\Targeting\TargetSubject;

function subject(array $overrides = []): TargetSubject
{
    $values = array_merge([
        'programs' => [10], 'org_units' => [3, 2, 1], 'admission_year' => 2023, 'year_level' => 3,
        'status' => 'enrolled', 'standing' => 'good', 'tags' => ['exchange'],
    ], $overrides);

    return new readonly class($values) implements TargetSubject
    {
        public function __construct(private array $values) {}

        public function targetProgramIds(): array
        {
            return $this->values['programs'];
        }

        public function targetOrgUnitIds(): array
        {
            return $this->values['org_units'];
        }

        public function targetAdmissionYear(): ?int
        {
            return $this->values['admission_year'];
        }

        public function targetYearLevel(): ?int
        {
            return $this->values['year_level'];
        }

        public function targetStudentStatus(): ?string
        {
            return $this->values['status'];
        }

        public function targetAcademicStanding(): ?string
        {
            return $this->values['standing'];
        }

        public function targetTags(): array
        {
            return $this->values['tags'];
        }
    };
}

it('matches everyone when empty', function () {
    expect(TargetCriteria::everyone()->matches(subject()))->toBeTrue()
        ->and(TargetCriteria::everyone()->isEveryone())->toBeTrue();
});

it('requires every non-empty criterion to match', function (TargetCriteria $criteria, bool $expected) {
    expect($criteria->matches(subject()))->toBe($expected);
})->with([
    'year level in list' => [new TargetCriteria(yearLevels: [3, 4]), true],
    'year level not in list' => [new TargetCriteria(yearLevels: [4]), false],
    'college matches through ancestors' => [new TargetCriteria(orgUnitIds: [1]), true],
    'other org unit' => [new TargetCriteria(orgUnitIds: [99]), false],
    'admission year inside range' => [new TargetCriteria(admissionYearFrom: 2020, admissionYearTo: 2023), true],
    'admission year before range' => [new TargetCriteria(admissionYearFrom: 2024), false],
    'program and level both match' => [new TargetCriteria(programIds: [10], yearLevels: [3]), true],
    'program matches, level does not' => [new TargetCriteria(programIds: [10], yearLevels: [1]), false],
    'status' => [new TargetCriteria(studentStatuses: ['on_leave']), false],
    'tag' => [new TargetCriteria(tags: ['exchange', 'veteran']), true],
]);

it('does not match a year range when the subject has no admission year', function () {
    expect((new TargetCriteria(admissionYearTo: 2030))->matches(subject(['admission_year' => null])))->toBeFalse();
});

it('round-trips through arrays and stores only non-empty criteria', function () {
    $criteria = TargetCriteria::fromArray(['year_levels' => ['1', '2'], 'admission_year_from' => '2020', 'program_ids' => []]);

    expect($criteria->yearLevels)->toBe([1, 2])
        ->and($criteria->toArray())->toBe(['admission_year_from' => 2020, 'year_levels' => [1, 2]]);
});

it('rejects an inverted admission year range', function () {
    new TargetCriteria(admissionYearFrom: 2025, admissionYearTo: 2020);
})->throws(InvalidArgumentException::class);
