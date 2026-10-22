<?php

namespace App\Filament\Admin\Resources\Reference\Terms\RelationManagers;

use App\Domain\Term\Actions\CopySessionWindows;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Term\Term;
use App\Models\Term\TermSession;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Sessions of a term. The full-term session is created with the term and follows its
 * dates; other sessions (halves, intensive blocks) are added here.
 */
class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('reference.resources.term_sessions.plural_label');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        /** @var Term $term */
        $term = $this->getOwnerRecord();

        return $schema->components([
            TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(32)->alphaDash()
                ->disabled(fn (?TermSession $record) => $record->is_full_term ?? false)
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('term_id', $term->id)),
            TextInput::make('sort_order')->label(__('reference.fields.sort_order'))->integer()->default(0),
            TranslatableFields::make('name', __('reference.fields.name')),
            DatePicker::make('start_date')->label(__('reference.fields.start_date'))->required()
                ->minDate($term->start_date)->maxDate($term->end_date)
                ->disabled(fn (?TermSession $record) => $record->is_full_term ?? false),
            DatePicker::make('end_date')->label(__('reference.fields.end_date'))->required()->afterOrEqual('start_date')
                ->minDate($term->start_date)->maxDate($term->end_date)
                ->disabled(fn (?TermSession $record) => $record->is_full_term ?? false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code')),
                TextColumn::make('name')->label(__('reference.fields.name')),
                TextColumn::make('start_date')->label(__('reference.fields.start_date'))->date(),
                TextColumn::make('end_date')->label(__('reference.fields.end_date'))->date(),
                IconColumn::make('is_full_term')->label(__('reference.fields.is_full_term'))->boolean(),
                TextColumn::make('windows_count')->counts('windows')->label(__('reference.resources.term_windows.plural_label')),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                EditAction::make()->mutateRecordDataUsing(fn (array $data, TermSession $record) => [...$data, 'name' => $record->getTranslations('name')]),
                Action::make('copyWindows')->label(__('reference.actions.copy_session_windows'))->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->visible(fn (TermSession $record) => ! $record->is_full_term)
                    ->authorize(fn () => $this->canCreate())
                    ->modalDescription(__('reference.help.copy_session_windows'))
                    ->schema([
                        Select::make('source_session_id')->label(__('reference.fields.source_session'))->required()
                            ->options(fn (TermSession $record) => TermSession::query()->where('term_id', $record->term_id)->whereKeyNot($record->id)->get()->pluck('name', 'id'))
                            ->default(fn (TermSession $record) => TermSession::query()->where('term_id', $record->term_id)->where('is_full_term', true)->value('id')),
                    ])
                    ->action(function (TermSession $record, array $data, CopySessionWindows $copy) {
                        /** @var User $user */
                        $user = Auth::user();
                        $count = $copy->handle(TermSession::query()->findOrFail($data['source_session_id']), $record, $user);
                        Notification::make()->success()->title(trans_choice('reference.notifications.session_windows_copied', $count))->send();
                    }),
                SafeDeleteAction::make()->visible(fn (TermSession $record) => ! $record->is_full_term),
            ]);
    }
}
