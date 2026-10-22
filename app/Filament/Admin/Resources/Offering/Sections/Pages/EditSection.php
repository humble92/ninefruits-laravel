<?php

namespace App\Filament\Admin\Resources\Offering\Sections\Pages;

use App\Domain\Offering\Actions\ChangeSectionStatus;
use App\Domain\Offering\Actions\UpdateSection;
use App\Domain\Offering\Enums\SectionStatus;
use App\Filament\Admin\Resources\Offering\Sections\SectionResource;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TargetCriteriaFields;
use App\Models\Offering\Section;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class EditSection extends EditRecord
{
    protected static string $resource = SectionResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Section $section */
        $section = $this->getRecord();

        return [
            ...$data,
            'note' => $section->getTranslations('note'),
            'eligibility' => TargetCriteriaFields::toState($section->eligibility),
            'exclusion' => TargetCriteriaFields::toState($section->exclusion),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Section $record */
        return app(UpdateSection::class)->handle($record, [
            ...$data,
            'eligibility' => TargetCriteriaFields::fromState($data['eligibility'] ?? null),
            'exclusion' => TargetCriteriaFields::fromState($data['exclusion'] ?? null),
        ], $this->user());
    }

    protected function getHeaderActions(): array
    {
        $status = fn (string $name, SectionStatus $to, Heroicon $icon, string $color, array $visibleFrom) => Action::make($name)
            ->label(__('offering.actions.'.$name))->icon($icon)->color($color)
            ->authorize(fn (Section $record) => Gate::allows('update', $record))
            ->visible(fn (Section $record) => in_array($record->status, $visibleFrom, true))
            ->requiresConfirmation()
            ->schema($to === SectionStatus::Cancelled ? [Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000)] : [])
            ->action(function (Section $record, array $data, ChangeSectionStatus $change) use ($to) {
                $change->handle($record, $to, $this->user(), $data['reason'] ?? null);
                $this->refreshFormData(['status']);
                Notification::make()->success()->title(__('offering.notifications.status_changed', ['status' => $to->label()]))->send();
            });

        return [
            Action::make('syllabus')->label(__('offering.actions.syllabus'))->icon(Heroicon::OutlinedDocumentText)->color('gray')
                ->url(fn (Section $record) => route('portal.syllabus.edit', $record)),
            $status('publish', SectionStatus::Published, Heroicon::OutlinedEye, 'success', [SectionStatus::Draft]),
            $status('unpublish', SectionStatus::Draft, Heroicon::OutlinedEyeSlash, 'gray', [SectionStatus::Published]),
            $status('cancel', SectionStatus::Cancelled, Heroicon::OutlinedXCircle, 'danger', [SectionStatus::Draft, SectionStatus::Published]),
            $status('reopen', SectionStatus::Draft, Heroicon::OutlinedArrowUturnLeft, 'gray', [SectionStatus::Cancelled]),
            SafeDeleteAction::make()->visible(fn (Section $record) => Gate::allows('delete', $record)),
        ];
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
