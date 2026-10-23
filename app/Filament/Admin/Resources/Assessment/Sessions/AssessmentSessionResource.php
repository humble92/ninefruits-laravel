<?php

namespace App\Filament\Admin\Resources\Assessment\Sessions;

use App\Domain\Assessment\Actions\RecordAssessmentResult;
use App\Domain\Assessment\Services\AssessmentResultImporter;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Assessment\Sessions\Pages\CreateAssessmentSession;
use App\Filament\Admin\Resources\Assessment\Sessions\Pages\EditAssessmentSession;
use App\Filament\Admin\Resources\Assessment\Sessions\Pages\ListAssessmentSessions;
use App\Filament\Admin\Resources\Assessment\Sessions\RelationManagers\RegistrationsRelationManager;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Assessment\AssessmentSession;
use App\Models\Assessment\AssessmentType;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

/**
 * Sittings of a kind (legacy CET_HISTORY: date and cut line only): the registration
 * window, the capacity, the registrants with their results, publication and CSV import.
 */
class AssessmentSessionResource extends Resource
{
    protected static ?string $model = AssessmentSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Assessments;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'assessments/sessions';

    public static function getModelLabel(): string
    {
        return __('assessment.resources.sessions.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('assessment.resources.sessions.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('assessment_type_id')->label(__('assessment.fields.type'))->required()
                ->options(fn () => AssessmentType::query()->where('is_active', true)->orderBy('sort_order')->get()->pluck('name', 'id'))
                ->disabled(fn (?AssessmentSession $record) => $record !== null)->dehydrated(),
            DatePicker::make('held_date')->label(__('assessment.fields.held_date'))->required()
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule, callable $get) => $rule->where('assessment_type_id', $get('assessment_type_id'))),
            TextInput::make('venue')->label(__('assessment.fields.venue'))->maxLength(120),
            TextInput::make('capacity')->label(__('assessment.fields.capacity'))->integer()->minValue(1),
            DateTimePicker::make('registration_opens_at')->label(__('assessment.fields.registration_opens_at'))->seconds(false),
            DateTimePicker::make('registration_closes_at')->label(__('assessment.fields.registration_closes_at'))->seconds(false)->after('registration_opens_at'),
            TextInput::make('pass_score')->label(__('assessment.fields.pass_score'))->numeric()->minValue(0)->helperText(__('assessment.help.session_pass_score')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('type')->withCount(['registrations as registrants_count' => fn (Builder $q) => $q->whereIn('status', ['registered', 'absent']), 'results']))
            ->columns([
                TextColumn::make('held_date')->label(__('assessment.fields.held_date'))->date()->sortable(),
                TextColumn::make('type.name')->label(__('assessment.fields.type')),
                TextColumn::make('venue')->label(__('assessment.fields.venue'))->placeholder('-'),
                TextColumn::make('registration_closes_at')->label(__('assessment.fields.registration_closes_at'))->dateTime()->placeholder('-'),
                TextColumn::make('registrants_count')->label(__('assessment.fields.registrants'))
                    ->formatStateUsing(fn (int $state, AssessmentSession $record) => $state.($record->capacity !== null ? ' / '.$record->capacity : '')),
                TextColumn::make('results_count')->label(__('assessment.fields.results')),
                TextColumn::make('results_published_at')->label(__('assessment.fields.published_at'))->dateTime()->placeholder('-'),
            ])
            ->defaultSort('held_date', 'desc')
            ->filters([
                SelectFilter::make('assessment_type_id')->label(__('assessment.fields.type'))
                    ->options(fn () => AssessmentType::query()->orderBy('sort_order')->get()->pluck('name', 'id')),
            ])
            ->recordActions([EditAction::make(), self::publishAction(), self::importAction()]);
    }

    public static function publishAction(): Action
    {
        return Action::make('publish')->label(__('assessment.actions.publish'))->icon(Heroicon::OutlinedMegaphone)->color('success')
            ->visible(fn (AssessmentSession $record) => $record->results_published_at === null && self::actor()->can('assessments.record'))
            ->requiresConfirmation()->modalDescription(__('assessment.help.publish'))
            ->action(fn (AssessmentSession $record) => ShowsActionErrors::run(fn () => app(RecordAssessmentResult::class)->publish($record, self::actor())));
    }

    public static function importAction(): Action
    {
        return Action::make('import')->label(__('assessment.actions.import'))->icon(Heroicon::OutlinedArrowUpTray)->color('gray')
            ->visible(fn () => self::actor()->can('assessments.record'))
            ->modalDescription(__('assessment.help.import'))
            ->schema([FileUpload::make('file')->label(__('assessment.fields.file'))->required()->storeFiles(false)->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])])
            ->action(function (AssessmentSession $record, array $data, AssessmentResultImporter $importer) {
                /** @var TemporaryUploadedFile $file */
                $file = $data['file'];
                $summary = $importer->import($record, $file->getRealPath(), self::actor());
                $notification = Notification::make()->title(__('assessment.notifications.imported', ['recorded' => $summary['recorded'], 'absent' => $summary['absent'], 'errors' => count($summary['errors'])]))
                    ->body(implode("\n", array_slice($summary['errors'], 0, 10)));
                $summary['errors'] === [] ? $notification->success() : $notification->warning()->persistent();
                $notification->send();
            });
    }

    public static function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    public static function getRelations(): array
    {
        return [RegistrationsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssessmentSessions::route('/'),
            'create' => CreateAssessmentSession::route('/create'),
            'edit' => EditAssessmentSession::route('/{record}/edit'),
        ];
    }
}
