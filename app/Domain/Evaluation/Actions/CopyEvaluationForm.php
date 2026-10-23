<?php

namespace App\Domain\Evaluation\Actions;

use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Evaluation\EvaluationForm;
use App\Models\Evaluation\EvaluationQuestion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Copies a form with its questions, e.g. to change a form whose questions are locked by
 * responses.
 */
class CopyEvaluationForm
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(EvaluationForm $form, string $code, User $staff): EvaluationForm
    {
        if (! $staff->can(Permission::EvaluationsManage->value)) {
            throw new AuthorizationException;
        }

        $copy = DB::transaction(function () use ($form, $code) {
            $copy = $form->replicate()->fill(['code' => $code]);
            $copy->save();

            foreach ($form->questions()->get() as $question) {
                /** @var EvaluationQuestion $question */
                $question->replicate()->fill(['evaluation_form_id' => $copy->id])->save();
            }

            return $copy;
        });

        $this->audit->record('evaluation_form.copied', $copy, null, ['from' => $form->code], $staff);

        return $copy;
    }
}
