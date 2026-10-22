<?php

namespace Database\Factories\Organization;

use App\Domain\Organization\Enums\OrgUnitChangeType;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\OrgUnitSuccession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrgUnitSuccession>
 */
class OrgUnitSuccessionFactory extends Factory
{
    protected $model = OrgUnitSuccession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'predecessor_org_unit_id' => OrgUnit::factory(),
            'successor_org_unit_id' => OrgUnit::factory(),
            'change_type' => OrgUnitChangeType::Renamed,
            'effective_date' => '2020-03-01',
        ];
    }
}
