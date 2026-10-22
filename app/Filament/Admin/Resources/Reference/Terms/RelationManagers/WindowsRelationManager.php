<?php

namespace App\Filament\Admin\Resources\Reference\Terms\RelationManagers;

use App\Domain\Term\Enums\TermWindowType;
use App\Filament\Admin\Resources\Reference\OrgUnits\OrgUnitResource;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Organization\Program;
use App\Models\Term\TermWindow;
use App\Support\Targeting\TargetCriteria;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Business windows of a term. Several windows of one type with different eligibility make
 * a staggered opening (e.g. fourth-year students first).
 */
class WindowsRelationManager extends RelationManager
{
    protected static string $relationship = 'windows';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('reference.resources.term_windows.plural_label');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('window_type')->label(__('reference.fields.window_type'))
                ->options(collect(TermWindowType::cases())->mapWithKeys(fn (TermWindowType $type) => [$type->value => $type->label()]))
                ->required(),
            TranslatableFields::make('name', __('reference.fields.window_name'), required: false),
            DateTimePicker::make('opens_at')->label(__('reference.fields.opens_at'))->seconds(false)->required(),
            DateTimePicker::make('closes_at')->label(__('reference.fields.closes_at'))->seconds(false)->required()->after('opens_at'),
            Section::make(__('reference.fields.eligibility'))
                ->description(__('reference.help.eligibility'))
                ->statePath('eligibility')
                ->columns(2)
                ->schema([
                    CheckboxList::make('year_levels')->label(__('reference.fields.year_levels'))
                        ->options(collect(range(1, 6))->mapWithKeys(fn (int $level) => [$level => (string) $level])->all())->columns(6),
                    TextInput::make('admission_year_from')->label(__('reference.fields.admission_year_from'))->integer()->minValue(1900)->maxValue(2200),
                    TextInput::make('admission_year_to')->label(__('reference.fields.admission_year_to'))->integer()->minValue(1900)->maxValue(2200)->gte('admission_year_from'),
                    Select::make('program_ids')->label(__('reference.fields.programs'))->multiple()
                        ->options(fn () => Program::query()->effectiveOn()->orderBy('code')->get()->mapWithKeys(fn (Program $program) => [$program->id => $program->code.' '.$program->name])),
                    Select::make('org_unit_ids')->label(__('reference.fields.org_units'))->multiple()
                        ->options(fn () => OrgUnitResource::unitOptions())
                        ->helperText(__('reference.help.org_unit_descendants')),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('window_type')->label(__('reference.fields.window_type'))
                    ->formatStateUsing(fn (TermWindowType $state) => $state->label())->badge(),
                TextColumn::make('name')->label(__('reference.fields.window_name'))->placeholder('-'),
                TextColumn::make('opens_at')->label(__('reference.fields.opens_at'))->dateTime()->sortable(),
                TextColumn::make('closes_at')->label(__('reference.fields.closes_at'))->dateTime(),
                TextColumn::make('eligibility')->label(__('reference.fields.eligibility'))
                    ->state(fn (TermWindow $record) => $record->eligibility->isEveryone() ? __('reference.eligibility.everyone') : __('reference.eligibility.restricted')),
            ])
            ->defaultSort('opens_at')
            ->headerActions([
                CreateAction::make()->mutateDataUsing(fn (array $data) => self::normalizeEligibility($data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, TermWindow $record) => [
                        ...$data,
                        'name' => $record->getTranslations('name'),
                        'eligibility' => $record->eligibility->toArray(),
                    ])
                    ->mutateDataUsing(fn (array $data) => self::normalizeEligibility($data)),
                SafeDeleteAction::make(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function normalizeEligibility(array $data): array
    {
        $data['eligibility'] = TargetCriteria::fromArray($data['eligibility'] ?? null);

        return $data;
    }
}
