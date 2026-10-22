<?php

namespace App\Domain\Term\Actions;

use App\Domain\Security\Services\AuditLogger;
use App\Domain\Term\Enums\TermStatus;
use App\Models\Term\Term;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * The only way a term's operating state changes. Transitions follow TermStatus::allowedNext().
 */
class ChangeTermStatus
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Term $term, TermStatus $status, ?string $reason = null, ?User $changedBy = null): Term
    {
        $from = $term->status;

        if (! $from->canMoveTo($status)) {
            throw ValidationException::withMessages([
                'status' => __('reference.errors.term_status_transition', ['from' => $from->label(), 'to' => $status->label()]),
            ]);
        }

        $term->forceFill(['status' => $status])->save();

        $this->audit->record('term.status_changed', $term, $reason, ['from' => $from->value, 'to' => $status->value], $changedBy);

        return $term;
    }
}
