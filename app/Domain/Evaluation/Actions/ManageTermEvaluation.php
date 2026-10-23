<?php

namespace App\Domain\Evaluation\Actions;

use App\Domain\Evaluation\Settings\EvaluationSettings;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Evaluation\EvaluationForm;
use App\Models\Evaluation\EvaluationResponse;
use App\Models\Evaluation\TermEvaluation;
use App\Models\Term\Term;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Sets up a term's course evaluation (form and minimums) and publishes its results to the
 * instructors. The evaluation period is the course evaluation window of the sessions.
 */
class ManageTermEvaluation
{
    public function __construct(
        private readonly EvaluationSettings $settings,
        private readonly AuditLogger $audit,
    ) {}

    public function prepare(Term $term, EvaluationForm $form, ?User $staff, ?int $minimumResponses = null, ?int $minimumPercent = null): TermEvaluation
    {
        $this->authorize($staff);
        $evaluation = TermEvaluation::query()->firstOrNew(['term_id' => $term->id]);

        if ($evaluation->exists && $evaluation->evaluation_form_id !== $form->id
            && EvaluationResponse::query()->where('term_id', $term->id)->exists()) {
            throw ValidationException::withMessages(['evaluation_form_id' => __('evaluation.errors.form_in_use')]);
        }

        $evaluation->fill([
            'evaluation_form_id' => $form->id,
            'minimum_responses' => $minimumResponses ?? ($evaluation->exists ? $evaluation->minimum_responses : $this->settings->minimum_responses),
            'minimum_response_percent' => $minimumPercent ?? ($evaluation->exists ? $evaluation->minimum_response_percent : $this->settings->minimum_response_percent),
        ])->save();
        $this->audit->record('evaluation.prepared', $evaluation, null, ['term' => $term->code, 'form' => $form->code], $staff);

        return $evaluation;
    }

    public function publish(TermEvaluation $evaluation, ?User $staff): TermEvaluation
    {
        $this->authorize($staff);

        if ($evaluation->results_published_at !== null) {
            return $evaluation;
        }

        $evaluation->forceFill(['results_published_at' => CarbonImmutable::now(), 'published_by_id' => $staff?->id])->save();
        $this->audit->record('evaluation.published', $evaluation, null, ['term_id' => $evaluation->term_id], $staff);

        return $evaluation;
    }

    private function authorize(?User $staff): void
    {
        if ($staff !== null && ! $staff->can(Permission::EvaluationsManage->value)) {
            throw new AuthorizationException;
        }
    }
}
