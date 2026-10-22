<?php

namespace App\Support\Targeting;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Who something applies to (docs/porting-plan.md section 10.6): programs, org units,
 * admission year range, year levels, student statuses, academic standings and tags. An
 * empty criterion does not restrict; all non-empty criteria must match. Used by term
 * windows now and by registration rules and section eligibility later.
 */
final readonly class TargetCriteria implements Castable
{
    /**
     * @param  list<int>  $programIds
     * @param  list<int>  $orgUnitIds
     * @param  list<int>  $yearLevels
     * @param  list<string>  $studentStatuses
     * @param  list<string>  $academicStandings
     * @param  list<string>  $tags
     */
    public function __construct(
        public array $programIds = [],
        public array $orgUnitIds = [],
        public ?int $admissionYearFrom = null,
        public ?int $admissionYearTo = null,
        public array $yearLevels = [],
        public array $studentStatuses = [],
        public array $academicStandings = [],
        public array $tags = [],
    ) {
        if ($admissionYearFrom !== null && $admissionYearTo !== null && $admissionYearFrom > $admissionYearTo) {
            throw new InvalidArgumentException('admissionYearFrom must not be after admissionYearTo.');
        }
    }

    public static function everyone(): self
    {
        return new self;
    }

    public function isEveryone(): bool
    {
        return $this->toArray() === [];
    }

    public function matches(TargetSubject $subject): bool
    {
        if ($this->programIds !== [] && array_intersect($this->programIds, $subject->targetProgramIds()) === []) {
            return false;
        }

        if ($this->orgUnitIds !== [] && array_intersect($this->orgUnitIds, $subject->targetOrgUnitIds()) === []) {
            return false;
        }

        $admissionYear = $subject->targetAdmissionYear();

        if (($this->admissionYearFrom !== null || $this->admissionYearTo !== null) && $admissionYear === null) {
            return false;
        }

        if ($this->admissionYearFrom !== null && $admissionYear < $this->admissionYearFrom) {
            return false;
        }

        if ($this->admissionYearTo !== null && $admissionYear > $this->admissionYearTo) {
            return false;
        }

        if ($this->yearLevels !== [] && ! in_array($subject->targetYearLevel(), $this->yearLevels, true)) {
            return false;
        }

        if ($this->studentStatuses !== [] && ! in_array($subject->targetStudentStatus(), $this->studentStatuses, true)) {
            return false;
        }

        if ($this->academicStandings !== [] && ! in_array($subject->targetAcademicStanding(), $this->academicStandings, true)) {
            return false;
        }

        return $this->tags === [] || array_intersect($this->tags, $subject->targetTags()) !== [];
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    public static function fromArray(?array $data): self
    {
        $data ??= [];
        $ints = fn (string $key) => array_values(array_map('intval', (array) ($data[$key] ?? [])));
        $strings = fn (string $key) => array_values(array_map('strval', (array) ($data[$key] ?? [])));
        $int = fn (string $key) => isset($data[$key]) && $data[$key] !== '' ? (int) $data[$key] : null;

        return new self(
            $ints('program_ids'),
            $ints('org_unit_ids'),
            $int('admission_year_from'),
            $int('admission_year_to'),
            $ints('year_levels'),
            $strings('student_statuses'),
            $strings('academic_standings'),
            $strings('tags'),
        );
    }

    /**
     * Only non-empty criteria, so "everyone" is stored as {}.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'program_ids' => $this->programIds,
            'org_unit_ids' => $this->orgUnitIds,
            'admission_year_from' => $this->admissionYearFrom,
            'admission_year_to' => $this->admissionYearTo,
            'year_levels' => $this->yearLevels,
            'student_statuses' => $this->studentStatuses,
            'academic_standings' => $this->academicStandings,
            'tags' => $this->tags,
        ], fn (mixed $value) => $value !== null && $value !== []);
    }

    /**
     * @param  array<mixed>  $arguments
     * @return CastsAttributes<self, self|array<string, mixed>|null>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes
        {
            public function get(Model $model, string $key, mixed $value, array $attributes): TargetCriteria
            {
                return TargetCriteria::fromArray($value === null ? null : (array) json_decode((string) $value, true));
            }

            public function set(Model $model, string $key, mixed $value, array $attributes): string
            {
                $criteria = $value instanceof TargetCriteria ? $value : TargetCriteria::fromArray(is_array($value) ? $value : null);

                return (string) json_encode((object) $criteria->toArray());
            }
        };
    }
}
