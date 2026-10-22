<?php

namespace Database\Factories\Person;

use App\Domain\Person\Enums\EmploymentType;
use App\Models\Organization\OrgUnit;
use App\Models\Person\Instructor;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Instructor>
 */
class InstructorFactory extends Factory
{
    protected $model = Instructor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'employee_number' => 'F'.fake()->unique()->numerify('#####'),
            'org_unit_id' => OrgUnit::factory(),
            'employment_type' => EmploymentType::FullTime,
            'academic_title' => ['en' => 'Professor', 'ko' => '교수', 'zh_CN' => '教授'],
            'hire_date' => fake()->dateTimeBetween('-20 years', '-1 year')->format('Y-m-d'),
        ];
    }
}
