<?php

namespace App\Filament\Admin\Resources\Reference\Terms\Pages;

use App\Domain\Term\Actions\ChangeTermStatus;
use App\Domain\Term\Enums\TermStatus;
use App\Filament\Admin\Resources\Reference\Terms\TermResource;
use App\Filament\Admin\Support\FillsTranslatableFields;
use App\Models\Term\Term;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class EditTerm extends EditRecord
{
    use FillsTranslatableFields;

    protected static string $resource = TermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('changeStatus')
                ->label(__('reference.actions.change_status'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->authorize(fn (Term $record) => Gate::allows('update', $record))
                ->visible(fn (Term $record) => $record->status->allowedNext() !== [])
                ->schema([
                    Select::make('status')->label(__('reference.fields.status'))
                        ->options(fn (Term $record) => collect($record->status->allowedNext())->mapWithKeys(fn (TermStatus $status) => [$status->value => $status->label()]))
                        ->required(),
                    Textarea::make('reason')->label(__('common.reason'))->maxLength(1000),
                ])
                ->action(function (Term $record, array $data, ChangeTermStatus $change) {
                    /** @var User $actor */
                    $actor = Auth::user();
                    $change->handle($record, TermStatus::from($data['status']), $data['reason'] ?? null, $actor);

                    Notification::make()->success()->title(__('reference.notifications.term_status_changed'))->send();
                }),
        ];
    }
}
