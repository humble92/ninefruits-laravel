<?php

namespace Database\Factories\Person;

use App\Domain\Person\Enums\AdvisorRole;
use App\Models\Person\AdvisorAssignment;
use App\Models\Person\Instructor;
use App\Models\Person\StudentRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdvisorAssignment>
 */
class AdvisorAssignmentFactory extends Factory
{
    protected $model = AdvisorAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_record_id' => StudentRecord::factory(),
            'instructor_id' => Instructor::factory(),
            'role' => AdvisorRole::Primary,
            'start_date' => now()->subYear()->toDateString(),
        ];
    }
}
