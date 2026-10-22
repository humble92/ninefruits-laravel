<?php

namespace App\Domain\Security\Data;

use App\Domain\Security\Enums\ScopeType;

/**
 * Where a user may use a permission. Query scopes (`visibleTo`) translate this into conditions.
 */
final readonly class PermissionScope
{
    /**
     * @param  list<int>  $orgUnitIds
     * @param  list<int>  $programIds
     */
    public function __construct(
        public bool $isGlobal = false,
        public array $orgUnitIds = [],
        public array $programIds = [],
        public bool $includesOwn = false,
    ) {}

    public static function none(): self
    {
        return new self;
    }

    public function isEmpty(): bool
    {
        return ! $this->isGlobal && $this->orgUnitIds === [] && $this->programIds === [] && ! $this->includesOwn;
    }

    public function merge(self $other): self
    {
        return new self(
            $this->isGlobal || $other->isGlobal,
            array_values(array_unique([...$this->orgUnitIds, ...$other->orgUnitIds])),
            array_values(array_unique([...$this->programIds, ...$other->programIds])),
            $this->includesOwn || $other->includesOwn,
        );
    }

    public static function fromAssignment(ScopeType $type, ?int $scopeId): self
    {
        return match ($type) {
            ScopeType::Global => new self(isGlobal: true),
            ScopeType::OrgUnit => new self(orgUnitIds: [(int) $scopeId]),
            ScopeType::Program => new self(programIds: [(int) $scopeId]),
        };
    }
}
