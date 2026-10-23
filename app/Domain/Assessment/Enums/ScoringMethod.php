<?php

namespace App\Domain\Assessment\Enums;

/**
 * Score: the total of the areas against the pass score (and area minimums). PassFail: staff decide.
 */
enum ScoringMethod: string
{
    case Score = 'score';
    case PassFail = 'pass_fail';

    public function label(): string
    {
        return __('assessment.scoring_methods.'.$this->value);
    }
}
