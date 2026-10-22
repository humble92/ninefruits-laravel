<?php

namespace Database\Factories\Term;

use App\Domain\Term\Enums\TermWindowType;
use App\Models\Term\Term;
use App\Models\Term\TermWindow;
use App\Support\Targeting\TargetCriteria;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TermWindow>
 */
class TermWindowFactory extends Factory
{
    protected $model = TermWindow::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'term_id' => Term::factory()->published(),
            'window_type' => TermWindowType::Registration,
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addWeek(),
        ];
    }

    public function ofType(TermWindowType $type): static
    {
        return $this->state(fn () => ['window_type' => $type]);
    }

    public function eligibleFor(TargetCriteria $criteria): static
    {
        return $this->state(fn () => ['eligibility' => $criteria]);
    }

    public function between(CarbonInterface $opensAt, CarbonInterface $closesAt): static
    {
        return $this->state(fn () => ['opens_at' => $opensAt, 'closes_at' => $closesAt]);
    }
}
