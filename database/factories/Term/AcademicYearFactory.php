<?php

namespace Database\Factories\Term;

use App\Models\Term\AcademicYear;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => (string) ($year = fake()->unique()->numberBetween(1990, 2090)),
            'name' => ['en' => $year.' academic year'],
            'start_date' => "{$year}-03-01",
            'end_date' => ($year + 1).'-02-28',
        ];
    }

    public function forYear(int $year): static
    {
        return $this->state(fn () => [
            'code' => (string) $year,
            'name' => ['en' => "{$year} academic year", 'ko' => "{$year}학년도"],
            'start_date' => "{$year}-03-01",
            'end_date' => CarbonImmutable::create($year + 1, 3, 1)->subDay()->toDateString(),
        ]);
    }
}
