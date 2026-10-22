<?php

namespace Database\Factories\Term;

use App\Domain\Term\Enums\TermStatus;
use App\Models\Term\AcademicYear;
use App\Models\Term\Term;
use App\Models\Term\TermType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Term>
 */
class TermFactory extends Factory
{
    protected $model = Term::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'term_type_id' => TermType::factory(),
            'code' => fake()->unique()->regexify('T[0-9]{4}-[A-Z]{2}'),
            'name' => ['en' => 'Term '.fake()->word()],
            'start_date' => '2026-03-02',
            'end_date' => '2026-06-20',
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => TermStatus::Published]);
    }

    public function between(string $start, string $end): static
    {
        return $this->state(fn () => ['start_date' => $start, 'end_date' => $end]);
    }
}
