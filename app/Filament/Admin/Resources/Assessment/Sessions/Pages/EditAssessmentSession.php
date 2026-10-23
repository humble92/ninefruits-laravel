<?php

namespace App\Filament\Admin\Resources\Assessment\Sessions\Pages;

use App\Domain\Assessment\Actions\ChangeSessionPassScore;
use App\Filament\Admin\Resources\Assessment\Sessions\AssessmentSessionResource;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Assessment\AssessmentSession;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditAssessmentSession extends EditRecord
{
    protected static string $resource = AssessmentSessionResource::class;

    public function getTitle(): string
    {
        /** @var AssessmentSession $session */
        $session = $this->getRecord();

        return $session->label();
    }

    /**
     * A changed cut line goes through the action (re-judgement, audit); the rest is a plain update.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var AssessmentSession $record */
        $passScore = filled($data['pass_score'] ?? null) ? (float) $data['pass_score'] : null;
        unset($data['pass_score']);
        $record->update($data);

        if ($passScore !== ($record->pass_score === null ? null : (float) $record->pass_score)) {
            /** @var User $user */
            $user = Auth::user();
            $summary = ShowsActionErrors::run(fn () => app(ChangeSessionPassScore::class)->handle($record, $passScore, $user, $data['pass_score_reason'] ?? null));
            Notification::make()->success()->title(__('assessment.notifications.rejudged', ['count' => $summary['rejudged'], 'passed' => count($summary['now_passed']), 'failed' => count($summary['now_failed'])]))->send();
        }

        return $record->refresh();
    }

    protected function getHeaderActions(): array
    {
        return [AssessmentSessionResource::importAction(), AssessmentSessionResource::publishAction()];
    }
}
