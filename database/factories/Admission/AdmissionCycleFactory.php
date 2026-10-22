<?php

namespace Database\Factories\Admission;

use App\Models\Admission\AdmissionCycle;
use App\Models\Admission\AdmissionCycleDocumentType;
use App\Models\Admission\AdmissionCycleProgram;
use App\Models\Institution\LookupValue;
use App\Models\Organization\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Published cycles open since last month. Use rolling(), closed(), withPrograms() and
 * withDocuments() for other shapes.
 *
 * @extends Factory<AdmissionCycle>
 */
class AdmissionCycleFactory extends Factory
{
    protected $model = AdmissionCycle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = (int) now()->addYear()->format('Y');

        return [
            'code' => 'AD'.$year.'-'.fake()->unique()->numerify('###'),
            'name' => ['en' => "{$year} regular admission", 'ko' => "{$year}학년도 정시 모집", 'zh_CN' => "{$year}年常规招生"],
            'opens_at' => now()->subMonth(),
            'closes_at' => now()->addMonth(),
            'is_published' => true,
        ];
    }

    public function rolling(): static
    {
        return $this->state(fn () => ['closes_at' => null, 'should_release_decisions_immediately' => true]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['opens_at' => now()->subMonths(3), 'closes_at' => now()->subMonth()]);
    }

    /**
     * @param  iterable<Program>  $programs
     */
    public function withPrograms(iterable $programs): static
    {
        return $this->afterCreating(function (AdmissionCycle $cycle) use ($programs) {
            foreach ($programs as $program) {
                AdmissionCycleProgram::query()->create(['admission_cycle_id' => $cycle->id, 'program_id' => $program->id]);
            }
        });
    }

    /**
     * @param  iterable<LookupValue>  $documentTypes  Required document types
     */
    public function withDocuments(iterable $documentTypes): static
    {
        return $this->afterCreating(function (AdmissionCycle $cycle) use ($documentTypes) {
            foreach ($documentTypes as $type) {
                AdmissionCycleDocumentType::query()->create(['admission_cycle_id' => $cycle->id, 'document_type_id' => $type->id, 'is_required' => true]);
            }
        });
    }
}
