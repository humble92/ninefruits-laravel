<?php

namespace App\Filament\Admin\Resources\Reference\Programs;

use App\Domain\Organization\Enums\DegreeLevel;
use App\Filament\Admin\Resources\Reference\Programs\Pages\ManagePrograms;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\Program;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProgramResource extends ReferenceResource
{
    protected static ?string $model = Program::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?int $navigationSort = 22;

    protected static function labelKey(): string
    {
        return 'programs';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(64)->unique(ignoreRecord: true),
            TranslatableFields::make('name', __('reference.fields.name')),
            TranslatableFields::make('degree_title', __('reference.fields.degree_title'), required: false),
            Select::make('degree_level')->label(__('reference.fields.degree_level'))
                ->options(collect(DegreeLevel::cases())->mapWithKeys(fn (DegreeLevel $level) => [$level->value => $level->label()]))
                ->required(),
            Select::make('org_unit_id')->label(__('reference.fields.org_unit'))
                ->relationship('orgUnit', 'code')
                ->getOptionLabelFromRecordUsing(fn (OrgUnit $record) => $record->code.' '.$record->name)
                ->searchable(['code'])
                ->preload()
                ->required(),
            TextInput::make('standard_duration_years')->label(__('reference.fields.standard_duration_years'))->numeric()->minValue(0.5)->maxValue(10),
            DatePicker::make('effective_start_date')->label(__('reference.fields.effective_start_date'))->required()->default(now()),
            DatePicker::make('effective_end_date')->label(__('reference.fields.effective_end_date'))->afterOrEqual('effective_start_date'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['orgUnit']))
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('reference.fields.name'))->searchable(query: fn ($query, string $search) => $query->whereRaw('util.i18n_search_text(name) ILIKE ?', ['%'.$search.'%'])),
                TextColumn::make('degree_level')->label(__('reference.fields.degree_level'))->formatStateUsing(fn (DegreeLevel $state) => $state->label())->badge(),
                TextColumn::make('orgUnit.name')->label(__('reference.fields.org_unit')),
                TextColumn::make('effective_start_date')->label(__('reference.fields.effective_start_date'))->date(),
                TextColumn::make('effective_end_date')->label(__('reference.fields.effective_end_date'))->date()->placeholder('-'),
            ])
            ->defaultSort('code')
            ->recordActions([
                EditAction::make(),
                SafeDeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePrograms::route('/'),
        ];
    }
}
