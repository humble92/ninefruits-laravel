<?php

namespace Tools\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * Calls of RegistrationRuleEngine::evaluateWith() outside database/seeders. Nullsafe calls
 * arrive here too: PHPStan also analyses `$a?->b()` as a method call. See RuleEngineAnswersCheck.
 *
 * @implements Rule<Node\Expr\CallLike>
 */
final class RuleEngineAnswersOnlyInSeedersRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node instanceof MethodCall) {
            return [];
        }

        return RuleEngineAnswersCheck::errors($node->var, $node->name, $scope);
    }
}
