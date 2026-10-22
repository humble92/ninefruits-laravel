<?php

namespace Database\Factories\Person;

use App\Domain\Person\Enums\StudentStatus;
use App\Models\Person\StudentStatusChangeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentStatusChangeType>
 */
class StudentStatusChangeTypeFactory extends Factory
{
    protected $model = StudentStatusChangeType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[a-z]{10}'),
            'name' => ['en' => 'Leave'],
            'to_status' => StudentStatus::OnLeave,
            'from_statuses' => [StudentStatus::Enrolled->value],
            'can_student_request' => true,
        ];
    }
}
