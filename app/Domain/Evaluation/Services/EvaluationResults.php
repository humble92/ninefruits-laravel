<?php

namespace App\Domain\Evaluation\Services;

use App\Domain\Evaluation\Data\SectionEvaluationResult;
use App\Domain\Evaluation\Enums\QuestionType;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Security\Enums\Permission;
use App\Models\Evaluation\EvaluationAnswer;
use App\Models\Evaluation\EvaluationQuestion;
use App\Models\Evaluation\EvaluationResponse;
use App\Models\Evaluation\TermEvaluation;
use App\Models\Offering\Section;
use App\Models\Registration\Enrollment;
use App\Models\User;

/**
 * Results of a section's evaluation (legacy counsel/result*.php, which averaged rounded
 * percentages, skipped every tenth question and showed results whatever the number of
 * responses). Answers are shown only when the term's results are published and the
 * section has at least the minimum responses and response rate; otherwise only the
 * counts, so few responses cannot point to a student. Comments come in random order.
 */
class EvaluationResults
{
    /**
     * Instructors see their sections once the term's results are published; staff with
     * evaluations.view or evaluations.manage for the offering unit see the response counts
     * any time and the answers once published.
     */
    public function canView(Section $section, User $user): bool
    {
        if ($section->isInScopeOf($user, Permission::EvaluationsView) || $section->isInScopeOf($user, Permission::EvaluationsManage)) {
            return true;
        }

        return $section->isTaughtBy($user)
            && TermEvaluation::query()->where('term_id', $section->term_id)->whereNotNull('results_published_at')->exists();
    }

    public function forSection(Section $section): SectionEvaluationResult
    {
        $termEvaluation = TermEvaluation::query()->where('term_id', $section->term_id)->with('form.questions')->first();
        $responses = EvaluationResponse::query()->where('section_id', $section->id)->count();
        $expected = Enrollment::query()->where('section_id', $section->id)
            ->whereIn('status', [EnrollmentStatus::Enrolled, EnrollmentStatus::Completed])
            ->whereHas('version', fn ($version) => $version->where('requires_evaluation', true))->count();
        $rate = $expected === 0 ? 0 : (int) floor($responses * 100 / $expected);
        // Answers only after publication (for staff too: following live results during the
        // period against completion times could point to students), and only with enough.
        $enough = $termEvaluation !== null && $termEvaluation->results_published_at !== null && $responses > 0
            && $responses >= $termEvaluation->minimum_responses && $rate >= $termEvaluation->minimum_response_percent;

        if (! $enough) {
            return new SectionEvaluationResult($section, $termEvaluation, $responses, $expected, $rate, false, [], [], null);
        }

        $form = $termEvaluation->form;
        $answers = EvaluationAnswer::query()->whereIn('evaluation_response_id', EvaluationResponse::query()->where('section_id', $section->id)->select('id'))->get()
            ->groupBy('evaluation_question_id');
        $questions = [];
        $comments = [];

        foreach ($form->questions as $question) {
            /** @var EvaluationQuestion $question */
            $given = $answers->get($question->id, collect());

            if ($question->question_type === QuestionType::Text) {
                $comments[$question->id] = $given->pluck('comment')->filter()->shuffle()->values()->all();

                continue;
            }

            $values = $given->pluck('value')->filter(fn (?int $value) => $value !== null)
                ->map(fn (int $value) => $question->is_reversed ? $form->scale_points + 1 - $value : $value);
            $distribution = [];

            for ($point = 1; $point <= $form->scale_points; $point++) {
                $distribution[$point] = $values->filter(fn (int $value) => $value === $point)->count();
            }

            $questions[$question->id] = [
                'count' => $values->count(),
                'mean' => $values->isEmpty() ? null : round($values->avg(), 2),
                'distribution' => $distribution,
            ];
        }

        $means = array_filter(array_column($questions, 'mean'), fn (?float $mean) => $mean !== null);

        return new SectionEvaluationResult($section, $termEvaluation, $responses, $expected, $rate, true, $questions, $comments,
            $means === [] ? null : round(array_sum($means) / count($means), 2));
    }
}
