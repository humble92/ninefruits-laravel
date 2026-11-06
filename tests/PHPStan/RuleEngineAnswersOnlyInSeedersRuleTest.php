<?php

namespace Tests\PHPStan;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use Tools\PHPStan\Rules\RuleEngineAnswersCallableOnlyInSeedersRule;
use Tools\PHPStan\Rules\RuleEngineAnswersOnlyInSeedersRule;

/**
 * Both rules (calls, first-class callables) on the same fixtures; each test picks one.
 *
 * @extends LarastanRuleTestCase<Rule<Node>>
 */
class RuleEngineAnswersOnlyInSeedersRuleTest extends LarastanRuleTestCase
{
    /** @var Rule<Node> */
    private Rule $rule;

    protected function getRule(): Rule
    {
        return $this->rule;
    }

    private const string MESSAGE = 'RegistrationRuleEngine::evaluateWith() takes the rule answers from the caller and is reserved for demo seeders (database/seeders). Use evaluate(), which checks every rule itself.';

    private const array FILES = [__DIR__.'/data/rule-engine-answers.php', __DIR__.'/data/database/seeders/allowed-rule-engine-answers.php'];

    public function test_it_reports_calls_outside_the_demo_seeders(): void
    {
        $this->rule = new RuleEngineAnswersOnlyInSeedersRule;

        $this->analyse(self::FILES, [[self::MESSAGE, 11], [self::MESSAGE, 12]]);
    }

    public function test_it_reports_first_class_callables_outside_the_demo_seeders(): void
    {
        $this->rule = new RuleEngineAnswersCallableOnlyInSeedersRule;

        $this->analyse(self::FILES, [[self::MESSAGE, 13]]);
    }
}
