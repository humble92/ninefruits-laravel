<?php

namespace Database\Factories\Curriculum;

use App\Domain\Curriculum\Enums\ProgramRole;
use App\Models\Curriculum\CurriculumRequirementSet;
use App\Models\Organization\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurriculumRequirementSet>
 */
class CurriculumRequirementSetFactory extends Factory
{
    protected $model = CurriculumRequirementSet::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'program_role' => ProgramRole::Primary,
            'catalog_year_from' => 2020,
            'name' => ['en' => 'Requirements from 2020'],
            'total_credits' => 130,
        ];
    }
}
