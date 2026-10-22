<?php

namespace App\Domain\Offering\Data;

/**
 * What CopySections did (or would do, for a dry run).
 */
final class SectionCopyReport
{
    /** @var list<string> */
    public array $created = [];

    /** @var array<string, string> Section label => reason */
    public array $skipped = [];

    /** @var array<string, list<string>> Section label => what was left out */
    public array $notes = [];

    public function created(string $label): void
    {
        $this->created[] = $label;
    }

    public function skip(string $label, string $reason): void
    {
        $this->skipped[$label] = $reason;
    }

    public function note(string $label, string $note): void
    {
        $this->notes[$label][] = $note;
    }

    /**
     * @return array{created: int, skipped: int, with_notes: int}
     */
    public function summary(): array
    {
        return ['created' => count($this->created), 'skipped' => count($this->skipped), 'with_notes' => count($this->notes)];
    }
}
