<?php

namespace App\Filament\Admin\Resources\AdditionalProgramRequests;

use App\Domain\Person\Actions\DecideAdditionalProgramRequest;
use App\Domain\Person\Enums\AdditionalProgramRequestStatus;
use App\Domain\Person\Enums\AdditionalProgramRole;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\AdditionalProgramRequests\Pages\ListAdditionalProgramRequests;
use App\Filament\Admin\Resources\StudentRecords\StudentRecordResource;
use App\Models\Person\AdditionalProgramRequest;
use App\Models\Person\StudentRecord;
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
 * Second major and minor applications. The target program's staff decide; the home
 * department sees its students' applications.
 */
class AdditionalProgramRequestResource extends Resource
{
    protected static ?string $model = AdditionalProgramRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare3Stack3d;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::StudentRecords;

    protected static ?int $navigationSort = 25;

    protected static ?string $slug = 'student-records/additional-programs';

    public static function getModelLabel(): string
    {
        return __('people.resources.additional_program_requests.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('people.resources.additional_program_requests.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Requests the user may decide, and those of students whose records they can see.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = self::actor();

        return parent::getEloquentQuery()->where(fn (Builder $query) => $query
            ->whereHas('studentRecord', fn (Builder $record) => StudentRecord::constrainVisibleTo($record, $user))
            ->orWhere(fn (Builder $decidable) => AdditionalProgramRequest::constrainDecidableBy($decidable, $user)));
    }

    public static function getNavigationBadge(): ?string
    {
        $query = AdditionalProgramRequest::query()->where('status', AdditionalProgramRequestStatus::Requested);
        AdditionalProgramRequest::constrainDecidableBy($query, self::actor());
        $count = $query->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        $decidable = fn (AdditionalProgramRequest $record) => $record->status === AdditionalProgramRequestStatus::Requested && Gate::allows('decide', $record);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['studentRecord.person', 'studentRecord.program', 'program', 'term']))
            ->columns([
                TextColumn::make('requested_at')->label(__('people.fields.requested_at'))->dateTime()->sortable(),
                TextColumn::make('studentRecord.student_number')->label(__('people.fields.student_number'))
                    ->url(fn (AdditionalProgramRequest $record) => StudentRecordResource::canView($record->studentRecord) ? StudentRecordResource::getUrl('view', ['record' => $record->studentRecord]) : null),
                TextColumn::make('name')->label(__('people.fields.name'))->state(fn (AdditionalProgramRequest $record) => $record->studentRecord->person->displayName()),
                TextColumn::make('studentRecord.program.name')->label(__('people.fields.primary_program')),
                TextColumn::make('program.name')->label(__('people.fields.requested_program')),
                TextColumn::make('role')->label(__('people.fields.program_role'))->formatStateUsing(fn (AdditionalProgramRole $state) => $state->label())->badge(),
                TextColumn::make('term.code')->label(__('people.fields.start_term')),
                TextColumn::make('statement')->label(__('people.fields.statement'))->limit(40)->tooltip(fn (AdditionalProgramRequest $record) => $record->statement),
                TextColumn::make('status')->label(__('people.fields.request_status'))->formatStateUsing(fn (AdditionalProgramRequestStatus $state) => $state->label())->badge()
                    ->color(fn (AdditionalProgramRequestStatus $state) => match ($state) {
                        AdditionalProgramRequestStatus::Requested => 'warning',
                        AdditionalProgramRequestStatus::Approved => 'success',
                        AdditionalProgramRequestStatus::Rejected => 'danger',
                        AdditionalProgramRequestStatus::Cancelled => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('people.fields.request_status'))
                    ->options(collect(AdditionalProgramRequestStatus::cases())->mapWithKeys(fn (AdditionalProgramRequestStatus $status) => [$status->value => $status->label()]))
                    ->default(AdditionalProgramRequestStatus::Requested->value),
                SelectFilter::make('role')->label(__('people.fields.program_role'))
                    ->options(collect(AdditionalProgramRole::cases())->mapWithKeys(fn (AdditionalProgramRole $role) => [$role->value => $role->label()])),
            ])
            ->defaultSort('requested_at')
            ->recordActions([
                Action::make('approve')->label(__('people.actions.approve'))->color('success')->icon(Heroicon::OutlinedCheck)
                    ->visible($decidable)
                    ->schema([Textarea::make('note')->label(__('people.fields.decision_note'))->maxLength(1000)])
                    ->action(function (AdditionalProgramRequest $record, array $data, DecideAdditionalProgramRequest $decide) {
                        $decide->approve($record, self::actor(), $data['note'] ?? null);
                        Notification::make()->success()->title(__('people.notifications.approved'))->send();
                    }),
                Action::make('reject')->label(__('people.actions.reject'))->color('danger')->icon(Heroicon::OutlinedXMark)
                    ->visible($decidable)
                    ->schema([Textarea::make('note')->label(__('people.fields.decision_note'))->required()->maxLength(1000)])
                    ->action(function (AdditionalProgramRequest $record, array $data, DecideAdditionalProgramRequest $decide) {
                        $decide->reject($record, self::actor(), $data['note']);
                        Notification::make()->success()->title(__('people.notifications.rejected'))->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAdditionalProgramRequests::route('/')];
    }

    private static function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
