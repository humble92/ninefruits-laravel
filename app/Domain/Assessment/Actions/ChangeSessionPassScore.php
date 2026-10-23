<?php

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\Enums\ScoringMethod;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Assessment\AssessmentComponent;
use App\Models\Assessment\AssessmentResult;
use App\Models\Assessment\AssessmentSession;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The cut line of a session (decided 2026-10-23): changed before the results are
 * published, usually after a hard exam; every result already entered is judged again from
 * its stored scores (total against the new score, the kind's area minimums unchanged) and
 * the audit record lists who changed. After publication the cut line is fixed and single
 * results go through corrections.
 */
class ChangeSessionPassScore
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @return array{rejudged: int, now_passed: list<string>, now_failed: list<string>}
     */
    public function handle(AssessmentSession $session, ?float $passScore, User $staff, ?string $reason = null): array
    {
        if (! $staff->can(Permission::AssessmentsManage->value)) {
            throw new AuthorizationException;
        }

        if ($session->results_published_at !== null) {
            throw ValidationException::withMessages(['pass_score' => __('assessment.errors.pass_score_published')]);
        }

        $session->loadMissing('type.components');
        $before = $session->pass_score;
        $summary = ['rejudged' => 0, 'now_passed' => [], 'now_failed' => []];

        DB::transaction(function () use ($session, $passScore, &$summary) {
            $session->forceFill(['pass_score' => $passScore])->save();
            $effective = $session->passScore();

            if ($session->type->scoring !== ScoringMethod::Score || $effective === null) {
                return;
            }

            $results = AssessmentResult::query()->where('assessment_session_id', $session->id)->with(['componentScores', 'studentRecord'])->get();
            $minimums = $session->type->components->filter(fn (AssessmentComponent $c) => $c->min_score !== null)->mapWithKeys(fn (AssessmentComponent $c) => [$c->id => (float) $c->min_score]);

            foreach ($results as $result) {
                if ($result->total_score === null) {
                    continue;
                }

                $passed = (float) $result->total_score >= (float) $effective;

                foreach ($result->componentScores as $score) {
                    if (isset($minimums[$score->assessment_component_id]) && (float) $score->score < $minimums[$score->assessment_component_id]) {
                        $passed = false;
                    }
                }

                $summary['rejudged']++;

                if ($passed !== $result->is_passed) {
                    $result->forceFill(['is_passed' => $passed])->save();
                    $summary[$passed ? 'now_passed' : 'now_failed'][] = $result->studentRecord->student_number;
                }
            }
        });

        $this->audit->record('assessment.pass_score_changed', $session, $reason, ['from' => $before, 'to' => $passScore, ...$summary], $staff);

        return $summary;
    }
}
