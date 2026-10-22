<?php

namespace App\Filament\Admin\Resources\Registration\Holds;

use App\Domain\Registration\Actions\ManageHolds;
use App\Domain\Registration\Enums\HoldType;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Registration\Holds\Pages\ListRegistrationHolds;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Filament\Admin\Support\StudentRecordSelect;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\Registration\RegistrationHold;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Registration holds (legacy SRDENY): placed and released with a reason, never deleted.
 */
class RegistrationHoldResource extends Resource
{
    protected static ?string $model = RegistrationHold::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Registration;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'registration/holds';

    public static function getModelLabel(): string
    {
        return __('registration.resources.holds.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('registration.resources.holds.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('studentRecord', fn (Builder $record) => StudentRecord::constrainVisibleTo($record, self::actor()));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('studentRecord.person'))
            ->columns([
                TextColumn::make('studentRecord.student_number')->label(__('people.fields.student_number'))->searchable(),
                TextColumn::make('name')->label(__('people.fields.name'))->state(fn (RegistrationHold $record) => $record->studentRecord->person->displayName())
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('studentRecord.person', fn (Builder $person) => Person::constrainToSearch($person, $search))),
                TextColumn::make('hold_type')->label(__('registration.fields.hold_type'))->formatStateUsing(fn (HoldType $state) => $state->label())->badge(),
                IconColumn::make('blocks_registration')->label(__('registration.fields.blocks_registration'))->boolean(),
                TextColumn::make('reason')->label(__('common.reason'))->limit(40)->tooltip(fn (RegistrationHold $record) => $record->reason),
                TextColumn::make('starts_at')->label(__('registration.fields.starts_at'))->dateTime()->sortable(),
                TextColumn::make('ends_at')->label(__('registration.fields.ends_at'))->dateTime()->placeholder('-'),
                TextColumn::make('released_at')->label(__('registration.fields.released_at'))->dateTime()->placeholder('-')
                    ->tooltip(fn (RegistrationHold $record) => $record->release_reason),
                TextColumn::make('source')->label(__('registration.fields.source'))->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                TernaryFilter::make('in_force')->label(__('registration.fields.in_force'))->default(true)
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('released_at')->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now())),
                        false: fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNotNull('released_at')->orWhere('ends_at', '<=', now())),
                    ),
                SelectFilter::make('hold_type')->label(__('registration.fields.hold_type'))
                    ->options(collect(HoldType::cases())->mapWithKeys(fn (HoldType $type) => [$type->value => $type->label()])),
            ])
            ->recordActions([
                Action::make('release')->label(__('registration.actions.release_hold'))->icon(Heroicon::OutlinedLockOpen)
                    ->visible(fn (RegistrationHold $record) => $record->released_at === null && Gate::allows('update', $record))
                    ->schema([Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000)])
                    ->action(fn (RegistrationHold $record, array $data) => ShowsActionErrors::run(fn () => app(ManageHolds::class)->release($record, $data['reason'], self::actor()))),
            ]);
    }

    public static function placeAction(): Action
    {
        return Action::make('placeHold')->label(__('registration.actions.place_hold'))->icon(Heroicon::OutlinedNoSymbol)
            ->authorize(fn () => Gate::allows('create', RegistrationHold::class))
            ->schema([
                StudentRecordSelect::make(),
                Select::make('hold_type')->label(__('registration.fields.hold_type'))->required()
                    ->options(collect(HoldType::cases())->mapWithKeys(fn (HoldType $type) => [$type->value => $type->label()])),
                Toggle::make('blocks_registration')->label(__('registration.fields.blocks_registration'))->default(true),
                Toggle::make('blocks_grades')->label(__('registration.fields.blocks_grades')),
                Toggle::make('blocks_certificates')->label(__('registration.fields.blocks_certificates')),
                DateTimePicker::make('starts_at')->label(__('registration.fields.starts_at'))->seconds(false),
                DateTimePicker::make('ends_at')->label(__('registration.fields.ends_at'))->seconds(false)->after('starts_at'),
                Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000),
            ])
            ->action(fn (array $data) => ShowsActionErrors::run(fn () => app(ManageHolds::class)->place(
                StudentRecord::query()->findOrFail($data['student_record_id']),
                HoldType::from($data['hold_type']),
                $data['reason'],
                self::actor(),
                [
                    'blocks_registration' => (bool) $data['blocks_registration'],
                    'blocks_grades' => (bool) $data['blocks_grades'],
                    'blocks_certificates' => (bool) $data['blocks_certificates'],
                    'starts_at' => filled($data['starts_at'] ?? null) ? CarbonImmutable::parse($data['starts_at']) : null,
                    'ends_at' => filled($data['ends_at'] ?? null) ? CarbonImmutable::parse($data['ends_at']) : null,
                ],
            )));
    }

    public static function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    public static function getPages(): array
    {
        return ['index' => ListRegistrationHolds::route('/')];
    }
}
