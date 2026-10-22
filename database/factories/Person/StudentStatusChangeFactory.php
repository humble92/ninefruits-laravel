<?php

namespace Database\Factories\Person;

use App\Domain\Person\Enums\StudentStatus;
use App\Models\Person\StudentRecord;
use App\Models\Person\StudentStatusChange;
use App\Models\Person\StudentStatusChangeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentStatusChange>
 */
class StudentStatusChangeFactory extends Factory
{
    protected $model = StudentStatusChange::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_record_id' => StudentRecord::factory(),
            'status_change_type_id' => StudentStatusChangeType::factory(),
            'from_status' => StudentStatus::Enrolled,
            'to_status' => StudentStatus::OnLeave,
            'effective_date' => now()->addWeek()->toDateString(),
            'reason' => 'Health',
            'requested_at' => now(),
        ];
    }
}
