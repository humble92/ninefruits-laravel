<?php

namespace App\Console\Commands\Evaluation;

use App\Console\Commands\AuditedCommand;
use App\Domain\Evaluation\Actions\ManageTermEvaluation;
use App\Models\Evaluation\EvaluationResponse;
use App\Models\Evaluation\TermEvaluation;
use App\Models\Term\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('evaluation:publish-results {term : Term code}')]
#[Description('Publish a term\'s course evaluation results to the instructors (sections with enough responses)')]
class PublishEvaluationResults extends AuditedCommand
{
    public function handle(ManageTermEvaluation $manage): int
    {
        $term = Term::query()->where('code', (string) $this->argument('term'))->first();
        $evaluation = $term === null ? null : TermEvaluation::query()->where('term_id', $term->id)->first();

        if ($evaluation === null) {
            $this->components->error('Unknown term code, or the term has no course evaluation.');

            return self::FAILURE;
        }

        $already = $evaluation->results_published_at !== null;
        $manage->publish($evaluation, $this->actor());
        $responses = EvaluationResponse::query()->where('term_id', $evaluation->term_id)->count();

        $this->recordResult(['responses' => $responses, 'already_published' => $already]);
        $this->components->info($already ? 'Already published.' : "Published ({$responses} response(s)).");

        return self::SUCCESS;
    }
}
