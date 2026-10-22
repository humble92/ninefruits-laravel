<?php

namespace App\Filament\Admin\Resources\Admissions\Applications\Pages;

use App\Domain\Admission\Actions\DecideApplication;
use App\Domain\Admission\Actions\MatriculateApplicant;
use App\Domain\Admission\Actions\ReleaseDecisions;
use App\Domain\Admission\Actions\RespondToOffer;
use App\Domain\Admission\Enums\ApplicationStatus;
use App\Filament\Admin\Resources\Admissions\Applications\ApplicationResource;
use App\Filament\Admin\Resources\StudentRecords\StudentRecordResource;
use App\Models\Admission\Application;
use App\Models\Term\Term;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

class ViewApplication extends ViewRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        $reviewable = fn (Application $record) => Gate::allows('review', $record);
        $decidable = fn (Application $record) => $record->status->isAwaitingDecision()
            || ($record->status->isDecided() && ! $record->isDecisionVisibleToApplicant());

        return [
            Action::make('startReview')->label(__('admissions.actions.start_review'))->icon(Heroicon::OutlinedEye)
                ->authorize($reviewable)
                ->visible(fn (Application $record) => $record->status === ApplicationStatus::Submitted)
                ->action(function (Application $record, DecideApplication $decide) {
                    $decide->startReview($record, ApplicationResource::actor());
                    $this->refreshFormData(['status']);
                }),
            Action::make('admit')->label(__('admissions.actions.admit'))->icon(Heroicon::OutlinedCheck)->color('success')
                ->authorize($reviewable)->visible($decidable)
                ->schema([Textarea::make('note')->label(__('admissions.fields.decision_note'))->maxLength(2000)])
                ->action(function (Application $record, array $data, DecideApplication $decide) {
                    $decide->admit($record, ApplicationResource::actor(), $data['note'] ?? null);
                    $this->refreshFormData(['status', 'decision_note']);
                    Notification::make()->success()->title(__('admissions.notifications.decided'))->send();
                }),
            Action::make('reject')->label(__('admissions.actions.reject'))->icon(Heroicon::OutlinedXMark)->color('danger')
                ->authorize($reviewable)->visible($decidable)
                ->schema([Textarea::make('note')->label(__('admissions.fields.decision_note'))->required()->maxLength(2000)])
                ->action(function (Application $record, array $data, DecideApplication $decide) {
                    $decide->reject($record, ApplicationResource::actor(), $data['note']);
                    $this->refreshFormData(['status', 'decision_note']);
                    Notification::make()->success()->title(__('admissions.notifications.decided'))->send();
                }),
            Action::make('release')->label(__('admissions.actions.release_decision'))->icon(Heroicon::OutlinedMegaphone)
                ->authorize($reviewable)
                ->visible(fn (Application $record) => $record->status->isDecided() && ! $record->isDecisionVisibleToApplicant())
                ->requiresConfirmation()->modalDescription(__('admissions.help.release_decisions'))
                ->action(function (Application $record, ReleaseDecisions $release) {
                    $release->handle([$record], ApplicationResource::actor());
                    Notification::make()->success()->title(trans_choice('admissions.notifications.released', 1))->send();
                }),
            Action::make('matriculate')->label(__('admissions.actions.matriculate'))->icon(Heroicon::OutlinedAcademicCap)->color('success')
                ->authorize(fn (Application $record) => Gate::allows('matriculate', $record))
                ->visible(fn (Application $record) => $record->status === ApplicationStatus::Confirmed && $record->student_record_id === null)
                ->schema([ApplicationResource::admissionTermField()])
                ->action(function (Application $record, array $data, MatriculateApplicant $matriculate) {
                    $studentRecord = $matriculate->handle($record, Term::query()->findOrFail($data['admission_term_id']), ApplicationResource::actor());
                    Notification::make()->success()->title(__('admissions.notifications.matriculated', ['number' => $studentRecord->student_number]))->send();
                    $this->redirect(StudentRecordResource::canView($studentRecord) ? StudentRecordResource::getUrl('view', ['record' => $studentRecord]) : ApplicationResource::getUrl('view', ['record' => $record]));
                }),
            ActionGroup::make([
                Action::make('recordResponse')->label(__('admissions.actions.record_response'))->icon(Heroicon::OutlinedPencilSquare)
                    ->authorize($reviewable)
                    ->visible(fn (Application $record) => $record->status === ApplicationStatus::Admitted && $record->isDecisionVisibleToApplicant())
                    ->schema([
                        Radio::make('response')->label(__('admissions.fields.response'))->required()
                            ->options(['accept' => __('admissions.portal.accept'), 'decline' => __('admissions.portal.decline')]),
                        Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000),
                    ])
                    ->action(function (Application $record, array $data, RespondToOffer $respond) {
                        $respond->handle($record, $data['response'] === 'accept', ApplicationResource::actor(), $data['reason']);
                        $this->refreshFormData(['status']);
                    }),
            ]),
        ];
    }
}
