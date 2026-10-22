<?php

namespace Database\Factories\Organization;

use App\Domain\Organization\Enums\DegreeLevel;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('P[A-Z]{3}[0-9]{2}'),
            'name' => ['en' => 'Bachelor of '.fake()->words(2, true)],
            'degree_level' => DegreeLevel::Bachelor,
            'org_unit_id' => OrgUnit::factory(),
            'standard_duration_years' => 4,
            'effective_start_date' => '2000-03-01',
        ];
    }
}
