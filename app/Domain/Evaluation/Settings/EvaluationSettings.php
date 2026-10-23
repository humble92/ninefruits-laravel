<?php

namespace App\Domain\Evaluation\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Course evaluation options (settings group `evaluations`).
 */
class EvaluationSettings extends Settings
{
    /** While a term's evaluation is open, its grades stay hidden until the student evaluated every course. */
    public bool $grades_require_evaluation;

    /** Refuse a response with the same answer to every scale question (legacy: all 1s or all 5s). */
    public bool $reject_uniform_answers;

    /** Defaults for a new term's evaluation: results only above both. */
    public int $minimum_responses;

    public int $minimum_response_percent;

    public static function group(): string
    {
        return 'evaluations';
    }
}
