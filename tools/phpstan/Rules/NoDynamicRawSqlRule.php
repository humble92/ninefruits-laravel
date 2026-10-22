<?php

namespace Tools\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Raw SQL must be a constant string. Values go in the bindings array, never into the SQL
 * text (docs/porting-plan.md section 11.1). Intentional exceptions, such as identifiers from
 * an allow list, carry an inline ignore for the identifier ninefruits.dynamicRawSql with a reason.
 *
 * @implements Rule<Node\Expr\CallLike>
 */
final class NoDynamicRawSqlRule implements Rule
{
    /** Query builder methods whose first argument is raw SQL. */
    private const array RAW_METHODS = [
        'raw', 'selectRaw', 'whereRaw', 'orWhereRaw', 'havingRaw', 'orHavingRaw',
        'orderByRaw', 'groupByRaw', 'fromRaw', 'joinRaw', 'whereExpression',
    ];

    /** Connection methods whose first argument is a complete SQL statement. */
    private const array STATEMENT_METHODS = [
        'select', 'selectOne', 'scalar', 'selectFromWriteConnection', 'cursor', 'insert', 'update',
        'delete', 'statement', 'affectingStatement', 'unprepared',
    ];

    public function getNodeType(): string
    {
        return Node\Expr\CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node instanceof MethodCall && ! $node instanceof StaticCall) {
            return [];
        }

        if (! $node->name instanceof Identifier || $node->isFirstClassCallable()) {
            return [];
        }

        $method = $node->name->toString();
        $args = $node->getArgs();

        if ($args === [] || ! $this->isRawSqlCall($node, $method, $scope)) {
            return [];
        }

        $type = $scope->getType($args[0]->value);

        if ($type->isString()->no() || $type->getConstantStrings() !== []) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Raw SQL passed to %s() is not a constant string. Use bindings for values, or an allow list for identifiers.',
                $method,
            ))->identifier('ninefruits.dynamicRawSql')->build(),
        ];
    }

    private function isRawSqlCall(MethodCall|StaticCall $node, string $method, Scope $scope): bool
    {
        if (in_array($method, self::RAW_METHODS, true)) {
            return true;
        }

        if (! in_array($method, self::STATEMENT_METHODS, true)) {
            return false;
        }

        if ($node instanceof StaticCall) {
            return $node->class instanceof Name
                && $scope->resolveName($node->class) === 'Illuminate\Support\Facades\DB';
        }

        return (new ObjectType('Illuminate\Database\ConnectionInterface'))
            ->isSuperTypeOf($scope->getType($node->var))
            ->yes();
    }
}
