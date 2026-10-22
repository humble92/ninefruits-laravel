<?php

namespace App\Domain\Registration\Data;

/**
 * All rule results of one attempt. The engine never stops at the first failure, so the
 * student and staff see every reason at once.
 */
final readonly class RuleEvaluation
{
    /**
     * @param  list<RuleResult>  $results
     */
    public function __construct(public array $results) {}

    public function isBlocked(): bool
    {
        foreach ($this->results as $result) {
            if ($result->blocks()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function blockingMessages(): array
    {
        return array_values(array_filter(array_map(fn (RuleResult $result) => $result->blocks() ? $result->message : null, $this->results)));
    }

    /**
     * Failed rules that only warn or were overridden.
     *
     * @return list<string>
     */
    public function warnings(): array
    {
        return array_values(array_filter(array_map(fn (RuleResult $result) => ! $result->passed && ! $result->blocks() ? $result->message : null, $this->results)));
    }

    public function detail(string $key): mixed
    {
        foreach ($this->results as $result) {
            if (array_key_exists($key, $result->details)) {
                return $result->details[$key];
            }
        }

        return null;
    }

    /**
     * @return list<array{code: string, passed: bool, severity: string, overridden: bool, message: string|null}>
     */
    public function toArray(): array
    {
        return array_map(fn (RuleResult $result) => $result->toArray(), $this->results);
    }
}
