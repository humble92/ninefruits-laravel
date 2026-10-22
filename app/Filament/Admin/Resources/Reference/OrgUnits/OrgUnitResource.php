<?php

namespace App\Filament\Admin\Resources\Reference\OrgUnits;

use App\Filament\Admin\Resources\Reference\OrgUnits\Pages\CreateOrgUnit;
use App\Filament\Admin\Resources\Reference\OrgUnits\Pages\EditOrgUnit;
use App\Filament\Admin\Resources\Reference\OrgUnits\Pages\ListOrgUnits;
use App\Filament\Admin\Resources\Reference\OrgUnits\RelationManagers\SuccessionsRelationManager;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\OrgUnitType;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Org units with validity periods. Units are not deleted after use; a reorganization ends
 * them and links successors (Reorganize action on the edit page).
 */
class OrgUnitResource extends ReferenceResource
{
    protected static ?string $model = OrgUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?int $navigationSort = 20;

    protected static function labelKey(): string
    {
        return 'org_units';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(64)->unique(ignoreRecord: true),
            Select::make('org_unit_type_id')->label(__('reference.fields.org_unit_type'))
                ->options(fn () => OrgUnitType::query()->orderBy('sort_order')->get()->mapWithKeys(fn (OrgUnitType $type) => [$type->id => $type->name]))
                ->required(),
            TranslatableFields::make('name', __('reference.fields.name')),
            TranslatableFields::make('short_name', __('reference.fields.short_name'), required: false),
            Select::make('parent_id')->label(__('reference.fields.parent'))
                ->options(fn (?OrgUnit $record) => self::unitOptions($record))
                ->searchable(),
            DatePicker::make('effective_start_date')->label(__('reference.fields.effective_start_date'))->required()->default(now()),
            DatePicker::make('effective_end_date')->label(__('reference.fields.effective_end_date'))->afterOrEqual('effective_start_date'),
            TextInput::make('sort_order')->label(__('reference.fields.sort_order'))->integer()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['type', 'parent']))
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('reference.fields.name'))
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereRaw('util.i18n_search_text(name) ILIKE ?', ['%'.$search.'%'])),
                TextColumn::make('type.name')->label(__('reference.fields.org_unit_type'))->badge(),
                TextColumn::make('parent.name')->label(__('reference.fields.parent'))->placeholder('-'),
                TextColumn::make('effective_start_date')->label(__('reference.fields.effective_start_date'))->date()->sortable(),
                TextColumn::make('effective_end_date')->label(__('reference.fields.effective_end_date'))->date()->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('org_unit_type_id')->label(__('reference.fields.org_unit_type'))->relationship('type', 'code'),
                Filter::make('current')->label(__('reference.filters.current_only'))->default()
                    ->query(fn (Builder $query) => OrgUnit::constrainToEffectiveOn($query)),
            ])
            ->defaultSort('code')
            ->recordActions([EditAction::make()]);
    }

    /**
     * Every unit except the record itself, labelled "code name".
     *
     * @return array<int, string>
     */
    public static function unitOptions(?OrgUnit $except = null): array
    {
        return OrgUnit::query()
            ->when($except !== null, fn (Builder $query) => $query->whereKeyNot($except->getKey()))
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (OrgUnit $unit) => [$unit->id => $unit->code.' '.$unit->name])
            ->all();
    }

    public static function getRelations(): array
    {
        return [SuccessionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrgUnits::route('/'),
            'create' => CreateOrgUnit::route('/create'),
            'edit' => EditOrgUnit::route('/{record}/edit'),
        ];
    }
}
