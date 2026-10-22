<?php

namespace Database\Factories\Admission;

use App\Domain\Admission\Enums\ApplicationStatus;
use App\Models\Admission\AdmissionCycle;
use App\Models\Admission\AdmissionCycleProgram;
use App\Models\Admission\Application;
use App\Models\Organization\Program;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Draft applications. submitted(), admitted(), released(), confirmed() and rejected() set
 * the later states directly (without notifications), for fixtures and demo data.
 *
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admission_cycle_id' => AdmissionCycle::factory(),
            'person_id' => Person::factory(),
            'program_id' => Program::factory(),
            'org_unit_id' => fn (array $attributes) => Program::query()->findOrFail($attributes['program_id'])->org_unit_id,
            'previous_school_name' => fake()->company().' High School',
            'previous_graduation_date' => now()->subMonths(fake()->numberBetween(1, 30))->toDateString(),
            'personal_statement' => fake()->paragraphs(2, true),
        ];
    }

    public function forCycle(AdmissionCycle $cycle, Program $program): static
    {
        return $this->state(fn () => ['admission_cycle_id' => $cycle->id, 'program_id' => $program->id, 'org_unit_id' => $program->org_unit_id])
            ->afterCreating(function () use ($cycle, $program) {
                AdmissionCycleProgram::query()->firstOrCreate(['admission_cycle_id' => $cycle->id, 'program_id' => $program->id]);
            });
    }

    public function submitted(): static
    {
        return $this->afterMaking(function (Application $application) {
            $application->forceFill([
                'status' => ApplicationStatus::Submitted,
                // Same shape as SubmitApplication, from a high range so it never meets real numbers.
                'application_number' => AdmissionCycle::query()->whereKey($application->admission_cycle_id)->value('code').'-'.fake()->unique()->numerify('9####'),
                'submitted_at' => now()->subDays(fake()->numberBetween(1, 20)),
            ]);
        });
    }

    public function admitted(): static
    {
        return $this->submitted()->afterMaking(function (Application $application) {
            $application->forceFill(['status' => ApplicationStatus::Admitted, 'decided_at' => now()->subDay(), 'review_started_at' => now()->subDays(2)]);
        });
    }

    public function rejected(): static
    {
        return $this->submitted()->afterMaking(function (Application $application) {
            $application->forceFill(['status' => ApplicationStatus::Rejected, 'decided_at' => now()->subDay(), 'decision_note' => 'Capacity', 'review_started_at' => now()->subDays(2)]);
        });
    }

    public function released(): static
    {
        return $this->afterMaking(function (Application $application) {
            $application->forceFill(['decision_released_at' => now()->subHours(2)]);
        });
    }

    public function confirmed(): static
    {
        return $this->admitted()->released()->afterMaking(function (Application $application) {
            $application->forceFill(['status' => ApplicationStatus::Confirmed, 'responded_at' => now()->subHour()]);
        });
    }
}
