<?php

namespace Database\Factories\Organization;

use App\Models\Organization\OrgUnit;
use App\Models\Organization\OrgUnitType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrgUnit>
 */
class OrgUnitFactory extends Factory
{
    protected $model = OrgUnit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'name' => ['en' => 'Department of '.fake()->unique()->words(2, true)],
            'org_unit_type_id' => OrgUnitType::factory(),
            'effective_start_date' => '2000-03-01',
        ];
    }

    public function under(OrgUnit $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }

    public function ended(string $lastDay): static
    {
        return $this->state(fn () => ['effective_end_date' => $lastDay]);
    }
}
