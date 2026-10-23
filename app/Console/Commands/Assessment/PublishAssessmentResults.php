<?php

namespace App\Console\Commands\Assessment;

use App\Console\Commands\AuditedCommand;
use App\Domain\Assessment\Actions\RecordAssessmentResult;
use App\Models\Assessment\AssessmentSession;
use App\Models\Assessment\AssessmentType;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Validation\ValidationException;

#[Signature('assessment:publish-results {type : Kind code} {held_date : Session date (YYYY-MM-DD)}')]
#[Description('Publish the results of a session to the students, as the admin screen does. Requires --actor.')]
class PublishAssessmentResults extends AuditedCommand
{
    public function handle(RecordAssessmentResult $results): int
    {
        $type = AssessmentType::query()->where('code', (string) $this->argument('type'))->first();
        $session = $type === null ? null : AssessmentSession::query()->where('assessment_type_id', $type->id)->whereDate('held_date', (string) $this->argument('held_date'))->first();
        $actor = $this->actor();

        if ($session === null || $actor === null) {
            $this->components->error($session === null ? 'Unknown session: give the kind code and the session date.' : '--actor is required.');

            return self::FAILURE;
        }

        try {
            $results->publish($session, $actor);
        } catch (ValidationException $e) {
            $this->components->error(implode(' ', array_merge(...array_values($e->errors()))));

            return self::FAILURE;
        }

        $count = $session->results()->count();
        $this->recordResult(['session_id' => $session->id, 'results' => $count]);
        $this->components->info("Published {$count} result(s) of {$session->label()}.");

        return self::SUCCESS;
    }
}
