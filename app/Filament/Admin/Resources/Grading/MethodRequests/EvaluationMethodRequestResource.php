<?php

namespace App\Filament\Admin\Resources\Grading\MethodRequests;

use App\Domain\Grading\Actions\ManageEvaluationMethodRequests;
use App\Domain\Grading\Enums\EvaluationMethod;
use App\Domain\Grading\Enums\RequestStatus;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Grading\MethodRequests\Pages\ListEvaluationMethodRequests;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Grading\EvaluationMethodRequest;
use App\Models\Offering\Section;
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
use UnitEnum;

/**
 * Instructors' requests to change a section's evaluation method, decided by the staff who
 * schedule the section (sections.manage for the offering unit).
 */
class EvaluationMethodRequestResource extends Resource
{
    protected static ?string $model = EvaluationMethodRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Grading;

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'grading/method-requests';

    public static function getModelLabel(): string
    {
        return __('grading.resources.method_requests.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('grading.resources.method_requests.plural_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = self::getEloquentQuery()->where('status', RequestStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('section', fn (Builder $section) => Section::constrainManageableBy($section, self::actor()));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['section.course', 'section.version', 'requestedBy', 'decidedBy']))
            ->columns([
                TextColumn::make('requested_at')->label(__('grading.fields.requested_at'))->dateTime()->sortable(),
                TextColumn::make('section')->label(__('offering.fields.section'))->state(fn (EvaluationMethodRequest $record) => $record->section->label().' '.$record->section->version->title)->wrap(),
                TextColumn::make('change')->label(__('grading.fields.change'))
                    ->state(fn (EvaluationMethodRequest $record) => $record->section->evaluation_method->label().' → '.$record->requested_method->label()),
                TextColumn::make('reason')->label(__('common.reason'))->limit(60)->tooltip(fn (EvaluationMethodRequest $record) => $record->reason)->wrap(),
                TextColumn::make('requestedBy.email')->label(__('grading.fields.requested_by')),
                TextColumn::make('status')->label(__('registration.fields.status'))->formatStateUsing(fn (RequestStatus $state) => $state->label())->badge()
                    ->color(fn (RequestStatus $state) => match ($state) {
                        RequestStatus::Pending => 'warning',
                        RequestStatus::Approved => 'success',
                        RequestStatus::Rejected => 'danger',
                        RequestStatus::Cancelled => 'gray',
                    }),
                TextColumn::make('decidedBy.email')->label(__('grading.fields.decided_by'))->placeholder('-')
                    ->description(fn (EvaluationMethodRequest $record) => $record->decision_note)->toggleable(),
            ])
            ->defaultSort('requested_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label(__('registration.fields.status'))
                    ->options(collect(RequestStatus::cases())->mapWithKeys(fn (RequestStatus $status) => [$status->value => $status->label()]))
                    ->default(RequestStatus::Pending->value),
                SelectFilter::make('requested_method')->label(__('grading.fields.evaluation_method'))
                    ->options(collect(EvaluationMethod::cases())->mapWithKeys(fn (EvaluationMethod $method) => [$method->value => $method->label()])),
            ])
            ->recordActions([
                Action::make('approve')->label(__('grading.actions.approve'))->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (EvaluationMethodRequest $record) => app(ManageEvaluationMethodRequests::class)->canDecide($record, self::actor()))
                    ->modalDescription(__('grading.help.approve_method'))
                    ->schema([Textarea::make('note')->label(__('grading.fields.decision_note'))->maxLength(1000)])
                    ->action(fn (EvaluationMethodRequest $record, array $data) => ShowsActionErrors::run(function () use ($record, $data) {
                        $cleared = app(ManageEvaluationMethodRequests::class)->approve($record, self::actor(), $data['note'] ?? null);
                        Notification::make()->success()->title(__('grading.notifications.method_approved', ['count' => $cleared]))->send();
                    })),
                Action::make('reject')->label(__('grading.actions.reject'))->icon(Heroicon::OutlinedXMark)->color('danger')
                    ->visible(fn (EvaluationMethodRequest $record) => app(ManageEvaluationMethodRequests::class)->canDecide($record, self::actor()))
                    ->schema([Textarea::make('note')->label(__('grading.fields.decision_note'))->required()->maxLength(1000)])
                    ->action(fn (EvaluationMethodRequest $record, array $data) => ShowsActionErrors::run(fn () => app(ManageEvaluationMethodRequests::class)->reject($record, self::actor(), $data['note']))),
            ]);
    }

    public static function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    public static function getPages(): array
    {
        return ['index' => ListEvaluationMethodRequests::route('/')];
    }
}
