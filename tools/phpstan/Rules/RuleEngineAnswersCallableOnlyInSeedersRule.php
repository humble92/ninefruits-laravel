<?php

namespace Tools\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Rules\Rule;

/**
 * $engine->evaluateWith(...) outside database/seeders: PHPStan hands first-class callables
 * to rules as MethodCallableNode, not as method calls. See RuleEngineAnswersCheck.
 *
 * @implements Rule<MethodCallableNode>
 */
final class RuleEngineAnswersCallableOnlyInSeedersRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCallableNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        return RuleEngineAnswersCheck::errors($node->getVar(), $node->getName(), $scope);
    }
}
