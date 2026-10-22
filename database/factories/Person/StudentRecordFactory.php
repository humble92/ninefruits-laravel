<?php

namespace Database\Factories\Person;

use App\Domain\Person\Enums\StudentStatus;
use App\Models\Organization\Program;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentRecord>
 */
class StudentRecordFactory extends Factory
{
    protected $model = StudentRecord::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->numberBetween(2022, 2026);

        return [
            'person_id' => Person::factory(),
            'student_number' => $year.fake()->unique()->numerify('#####'),
            'program_id' => Program::factory(),
            'org_unit_id' => fn (array $attributes) => Program::query()->findOrFail($attributes['program_id'])->org_unit_id,
            'admission_date' => "{$year}-03-01",
            'catalog_year' => $year,
            'year_level' => 1,
        ];
    }

    public function inProgram(Program $program): static
    {
        return $this->state(fn () => ['program_id' => $program->id, 'org_unit_id' => $program->org_unit_id]);
    }

    public function status(StudentStatus $status): static
    {
        return $this->afterMaking(function (StudentRecord $record) use ($status) {
            $record->status = $status;

            if ($status === StudentStatus::Graduated) {
                $record->graduation_date = $record->admission_date->addYears(4)->subDay();
            }
        });
    }

    public function graduated(): static
    {
        return $this->status(StudentStatus::Graduated);
    }
}
