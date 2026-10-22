<?php

namespace Database\Factories\Term;

use App\Models\Term\Term;
use App\Models\Term\TermSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A session in the first half of a new term. Use within() for a session of an existing term.
 *
 * @extends Factory<TermSession>
 */
class TermSessionFactory extends Factory
{
    protected $model = TermSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'term_id' => Term::factory(),
            'code' => 'S'.fake()->unique()->numerify('##'),
            'name' => ['en' => 'First half', 'ko' => '전반기', 'zh_CN' => '前半学期'],
            'start_date' => fn (array $attributes) => Term::query()->findOrFail($attributes['term_id'])->start_date,
            'end_date' => fn (array $attributes) => Term::query()->findOrFail($attributes['term_id'])->start_date->addWeeks(8),
        ];
    }

    public function within(Term $term, string $start, string $end, string $code = 'A'): static
    {
        return $this->state(fn () => ['term_id' => $term->id, 'code' => $code, 'start_date' => $start, 'end_date' => $end]);
    }
}
