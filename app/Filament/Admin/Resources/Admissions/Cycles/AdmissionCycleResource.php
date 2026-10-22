<?php

namespace App\Filament\Admin\Resources\Admissions\Cycles;

use App\Domain\Admission\Actions\ReleaseDecisions;
use App\Domain\Institution\Services\SupportedLocales;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Admissions\Cycles\Pages\CreateAdmissionCycle;
use App\Filament\Admin\Resources\Admissions\Cycles\Pages\EditAdmissionCycle;
use App\Filament\Admin\Resources\Admissions\Cycles\Pages\ListAdmissionCycles;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Admission\AdmissionCycle;
use App\Models\Admission\Application;
use App\Models\Institution\LookupValue;
use App\Models\Organization\Program;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Admission cycles: when applications are taken, which programs, which documents.
 */
class AdmissionCycleResource extends Resource
{
    protected static ?string $model = AdmissionCycle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Admissions;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'admissions/cycles';

    public static function getModelLabel(): string
    {
        return __('admissions.resources.cycles.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admissions.resources.cycles.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        $locales = app(SupportedLocales::class);

        return $schema->columns(1)->components([
            Section::make(__('admissions.sections.cycle'))->columns(2)->schema([
                TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(64)->alphaDash()->unique(ignoreRecord: true)
                    ->helperText(__('admissions.help.cycle_code')),
                Toggle::make('is_published')->label(__('admissions.fields.is_published'))->helperText(__('admissions.help.is_published')),
                TranslatableFields::make('name', __('reference.fields.name'))->columnSpanFull(),
                Fieldset::make(__('admissions.fields.description'))->columnSpanFull()->schema(array_map(
                    fn (string $locale) => Textarea::make("description.{$locale}")->label($locales->label($locale))->rows(3)->maxLength(2000),
                    $locales->all(),
                )),
                DateTimePicker::make('opens_at')->label(__('admissions.fields.opens_at'))->required()->seconds(false),
                DateTimePicker::make('closes_at')->label(__('admissions.fields.closes_at'))->after('opens_at')->seconds(false)
                    ->helperText(__('admissions.help.closes_at')),
                Toggle::make('should_release_decisions_immediately')->label(__('admissions.fields.should_release_decisions_immediately'))
                    ->helperText(__('admissions.help.should_release_decisions_immediately')),
                TextInput::make('offer_response_days')->label(__('admissions.fields.offer_response_days'))->integer()->minValue(1)->maxValue(365)
                    ->helperText(__('admissions.help.offer_response_days')),
            ]),
            Section::make(__('admissions.sections.programs'))->schema([
                Repeater::make('programs')->hiddenLabel()->relationship()->columns(2)->defaultItems(0)
                    ->addActionLabel(__('admissions.actions.add_program'))
                    ->schema([
                        Select::make('program_id')->label(__('reference.fields.programs'))
                            ->options(fn () => Program::query()->effectiveOn()->get()->sortBy('name')->pluck('name', 'id'))
                            ->searchable()->required()->distinct(),
                        TextInput::make('capacity')->label(__('admissions.fields.capacity'))->integer()->minValue(0),
                    ]),
            ]),
            Section::make(__('admissions.sections.documents'))->schema([
                Repeater::make('documentTypes')->hiddenLabel()->relationship()->columns(2)->defaultItems(0)
                    ->addActionLabel(__('admissions.actions.add_document_type'))
                    ->schema([
                        Select::make('document_type_id')->label(__('admissions.fields.document_type'))
                            ->options(fn () => LookupValue::query()->ofType(Application::DOCUMENT_TYPE_LOOKUP)->where('is_active', true)->orderBy('sort_order')->get()->pluck('name', 'id'))
                            ->required()->distinct(),
                        Toggle::make('is_required')->label(__('admissions.fields.is_required'))->default(true)->inline(false),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('applications'))
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('reference.fields.name')),
                TextColumn::make('opens_at')->label(__('admissions.fields.opens_at'))->dateTime()->sortable(),
                TextColumn::make('closes_at')->label(__('admissions.fields.closes_at'))->dateTime()->placeholder(__('admissions.fields.rolling')),
                IconColumn::make('is_published')->label(__('admissions.fields.is_published'))->boolean(),
                TextColumn::make('applications_count')->label(__('admissions.resources.applications.plural_label'))->numeric(),
            ])
            ->defaultSort('opens_at', 'desc')
            ->recordActions([EditAction::make(), self::releaseAction(), SafeDeleteAction::make()]);
    }

    /**
     * Releases every decided, unreleased application of the cycle within the user's review scope.
     */
    public static function releaseAction(): Action
    {
        return Action::make('releaseDecisions')
            ->label(__('admissions.actions.release_decisions'))
            ->icon(Heroicon::OutlinedMegaphone)
            ->authorize(fn () => Gate::allows('viewAny', Application::class))
            ->requiresConfirmation()
            ->modalDescription(__('admissions.help.release_decisions'))
            ->action(function (AdmissionCycle $record, ReleaseDecisions $release) {
                /** @var User $user */
                $user = Auth::user();
                $applications = Application::query()->visibleTo($user)->where('admission_cycle_id', $record->id)
                    ->whereIn('status', ['admitted', 'rejected'])->whereNull('decision_released_at')
                    ->with(['cycle', 'person.user', 'program'])->get();
                $count = $release->handle($applications, $user);

                Notification::make()->success()->title(trans_choice('admissions.notifications.released', $count))->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdmissionCycles::route('/'),
            'create' => CreateAdmissionCycle::route('/create'),
            'edit' => EditAdmissionCycle::route('/{record}/edit'),
        ];
    }
}
