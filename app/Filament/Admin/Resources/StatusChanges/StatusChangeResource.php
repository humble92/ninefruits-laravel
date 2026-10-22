<?php

namespace App\Filament\Admin\Resources\StatusChanges;

use App\Domain\Person\Actions\DecideStatusChange;
use App\Domain\Person\Enums\StatusChangeStatus;
use App\Domain\Person\Enums\StudentStatus;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\StatusChanges\Pages\ListStatusChanges;
use App\Filament\Admin\Resources\StudentRecords\StudentRecordResource;
use App\Models\Person\StudentRecord;
use App\Models\Person\StudentStatusChange;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Queue of status change requests from students. Approvers cannot decide requests they filed.
 */
class StatusChangeResource extends Resource
{
    protected static ?string $model = StudentStatusChange::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::StudentRecords;

    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return __('people.resources.status_changes.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('people.resources.status_changes.plural_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $query = self::getEloquentQuery();
        StudentStatusChange::constrainToPending($query);
        $count = $query->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User $user */
        $user = Auth::user();

        return parent::getEloquentQuery()->whereHas('studentRecord', fn (Builder $record) => StudentRecord::constrainVisibleTo($record, $user));
    }

    public static function historyColumns(Table $table): Table
    {
        return $table->columns(self::historyColumnList())->defaultSort('requested_at', 'desc');
    }

    /**
     * @return list<TextColumn>
     */
    public static function historyColumnList(): array
    {
        return [
            TextColumn::make('requested_at')->label(__('people.fields.requested_at'))->dateTime()->sortable(),
            TextColumn::make('type.name')->label(__('people.fields.status_change_type')),
            TextColumn::make('to_status')->label(__('people.fields.new_status'))->formatStateUsing(fn (StudentStatus $state) => $state->label()),
            TextColumn::make('effective_date')->label(__('people.fields.effective_date'))->date(),
            TextColumn::make('status')->label(__('people.fields.request_status'))->formatStateUsing(fn (StatusChangeStatus $state) => $state->label())->badge()
                ->color(fn (StatusChangeStatus $state) => match ($state) {
                    StatusChangeStatus::Requested => 'warning',
                    StatusChangeStatus::Approved => 'success',
                    StatusChangeStatus::Rejected => 'danger',
                    StatusChangeStatus::Cancelled => 'gray',
                }),
            TextColumn::make('reason')->label(__('common.reason'))->limit(40)->tooltip(fn (StudentStatusChange $record) => $record->reason),
            TextColumn::make('requestedBy.email')->label(__('people.fields.requested_by'))->placeholder('-'),
            TextColumn::make('decidedBy.email')->label(__('people.fields.decided_by'))->placeholder('-'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('requested_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['type', 'requestedBy', 'decidedBy', 'studentRecord.person']))
            ->columns([
                TextColumn::make('studentRecord.student_number')->label(__('people.fields.student_number'))->searchable()
                    ->url(fn (StudentStatusChange $record) => StudentRecordResource::getUrl('view', ['record' => $record->student_record_id])),
                TextColumn::make('student')->label(__('people.fields.name'))->state(fn (StudentStatusChange $record) => $record->studentRecord->person->displayName()),
                ...self::historyColumnList(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('people.fields.request_status'))
                    ->options(collect(StatusChangeStatus::cases())->mapWithKeys(fn (StatusChangeStatus $status) => [$status->value => $status->label()]))
                    ->default(StatusChangeStatus::Requested->value),
            ])
            ->recordActions([
                Action::make('approve')->label(__('people.actions.approve'))->color('success')->icon(Heroicon::OutlinedCheck)
                    ->visible(fn (StudentStatusChange $record) => $record->status === StatusChangeStatus::Requested)
                    ->authorize(fn (StudentStatusChange $record) => Gate::allows('decide', $record))
                    ->schema([Textarea::make('note')->label(__('people.fields.decision_note'))->maxLength(1000)])
                    ->action(function (StudentStatusChange $record, array $data, DecideStatusChange $decide) {
                        $decide->approve($record, self::actor(), $data['note'] ?? null);
                        Notification::make()->success()->title(__('people.notifications.approved'))->send();
                    }),
                Action::make('reject')->label(__('people.actions.reject'))->color('danger')->icon(Heroicon::OutlinedXMark)
                    ->visible(fn (StudentStatusChange $record) => $record->status === StatusChangeStatus::Requested)
                    ->authorize(fn (StudentStatusChange $record) => Gate::allows('decide', $record))
                    ->schema([Textarea::make('note')->label(__('people.fields.decision_note'))->required()->maxLength(1000)])
                    ->action(function (StudentStatusChange $record, array $data, DecideStatusChange $decide) {
                        $decide->reject($record, self::actor(), $data['note']);
                        Notification::make()->success()->title(__('people.notifications.rejected'))->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListStatusChanges::route('/')];
    }

    private static function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
