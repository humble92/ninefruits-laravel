<?php

namespace Database\Seeders\Demo\Support;

use App\Models\Organization\OrgUnit;
use App\Models\Person\StudentRecord;
use App\Models\Person\StudentRecordProgram;
use App\Support\Targeting\TargetSubject;
use Illuminate\Database\Eloquent\Collection;

/**
 * A student record's targeting facts, loaded once for many records. Answers like
 * StudentRecord, which queries its programs and unit ancestors and reads cast attributes
 * on every match; demo data matches thousands of students against every section.
 */
final readonly class StudentSubject implements TargetSubject
{
    /**
     * @param  list<int>  $programIds
     * @param  list<int>  $orgUnitIds
     * @param  list<string>  $tags
     */
    private function __construct(
        private array $programIds,
        private array $orgUnitIds,
        private ?int $admissionYear,
        private ?int $yearLevel,
        private ?string $studentStatus,
        private ?string $academicStanding,
        private array $tags,
    ) {}

    /**
     * @param  Collection<int, StudentRecord>  $records
     * @return array<int, self> By student record id
     */
    public static function forRecords(Collection $records): array
    {
        $additional = StudentRecordProgram::query()->whereIn('student_record_id', $records->modelKeys())->whereNull('end_date')
            ->with('program')->get()->groupBy('student_record_id');
        /** @var array<int, list<int>> $ancestors */
        $ancestors = [];
        $subjects = [];

        foreach ($records as $record) {
            /** @var \Illuminate\Support\Collection<int, StudentRecordProgram> $programs */
            $programs = $additional->get($record->id, collect());
            $units = array_unique([$record->org_unit_id, ...$programs->map(fn (StudentRecordProgram $program) => $program->program->org_unit_id)->all()]);
            $unitIds = [];

            foreach ($units as $unit) {
                $ancestors[$unit] ??= OrgUnit::query()->findOrFail($unit)->ancestorIds();
                $unitIds = [...$unitIds, ...$ancestors[$unit]];
            }

            $subjects[$record->id] = new self(
                [$record->program_id, ...$programs->pluck('program_id')->all()],
                array_values(array_unique($unitIds)),
                $record->targetAdmissionYear(),
                $record->targetYearLevel(),
                $record->targetStudentStatus(),
                $record->targetAcademicStanding(),
                $record->targetTags(),
            );
        }

        return $subjects;
    }

    public function targetProgramIds(): array
    {
        return $this->programIds;
    }

    public function targetOrgUnitIds(): array
    {
        return $this->orgUnitIds;
    }

    public function targetAdmissionYear(): ?int
    {
        return $this->admissionYear;
    }

    public function targetYearLevel(): ?int
    {
        return $this->yearLevel;
    }

    public function targetStudentStatus(): ?string
    {
        return $this->studentStatus;
    }

    public function targetAcademicStanding(): ?string
    {
        return $this->academicStanding;
    }

    public function targetTags(): array
    {
        return $this->tags;
    }
}
