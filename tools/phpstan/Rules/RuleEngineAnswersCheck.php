<?php

namespace Tools\PHPStan\Rules;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * RegistrationRuleEngine::evaluateWith() lets the caller answer the registration rules
 * itself; the engine only applies the term's rule configuration to those answers. Demo
 * seeders use it to decide thousands of registrations from data loaded once
 * (Database\Seeders\Demo\Support\BulkRegistrar). Anywhere else a caller could pass "passed"
 * for a rule that would fail, and the attempt log would still look legitimate, so outside
 * database/seeders the call is an error (identifier ninefruits.ruleEngineAnswers).
 * Application code uses evaluate(), where every rule checks the database itself.
 *
 * Shared by the rule for calls and the rule for first-class callables.
 */
final class RuleEngineAnswersCheck
{
    private const string ENGINE = 'App\Domain\Registration\Services\RegistrationRuleEngine';

    private const string METHOD = 'evaluateWith';

    /**
     * @return list<IdentifierRuleError>
     */
    public static function errors(Expr $var, Identifier|Expr $name, Scope $scope): array
    {
        if (! $name instanceof Identifier || strcasecmp($name->toString(), self::METHOD) !== 0) {
            return [];
        }

        if ($scope->getClassReflection()?->getName() === self::ENGINE || str_contains(str_replace('\\', '/', $scope->getFile()), '/database/seeders/')) {
            return [];
        }

        if (! (new ObjectType(self::ENGINE))->isSuperTypeOf($scope->getType($var))->yes()) {
            return [];
        }

        return [
            RuleErrorBuilder::message('RegistrationRuleEngine::evaluateWith() takes the rule answers from the caller and is reserved for demo seeders (database/seeders). Use evaluate(), which checks every rule itself.')
                ->identifier('ninefruits.ruleEngineAnswers')
                ->build(),
        ];
    }
}
