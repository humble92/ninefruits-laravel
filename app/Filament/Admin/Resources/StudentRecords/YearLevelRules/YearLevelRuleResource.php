<?php

namespace App\Filament\Admin\Resources\StudentRecords\YearLevelRules;

use App\Domain\Person\Actions\ManageYearLevels;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Person\YearLevelRule;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class YearLevelRuleResource extends Resource
{
    protected static ?string $model = YearLevelRule::class;

    protected static ?string $slug = 'settings/year-level-rules';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Settings;

    public static function getModelLabel(): string
    {
        return __('progression.rule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('progression.rules');
    }

    public static function canViewAny(): bool
    {
        return app(ManageYearLevels::class)->canConfigure(auth()->user());
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TranslatableFields::make('name', __('reference.fields.name')),
            Select::make('basis')->label(__('progression.basis'))->options(__('progression.bases'))->required()->live(),
            Repeater::make('thresholds')->label(__('progression.thresholds'))->helperText(__('progression.threshold_help'))
                ->schema([TextInput::make('minimum')->label(__('progression.minimum'))->numeric()->minValue(0.1)->maxValue(99999)->required()])
                ->minItems(1)->maxItems(9)->defaultItems(1)
                ->hidden(fn (Get $get): bool => $get('basis') === 'manual'),
            Toggle::make('is_default')->label(__('progression.default_rule'))->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('reference.fields.name')),
            TextColumn::make('basis')->label(__('progression.basis'))->formatStateUsing(fn (string $state) => __('progression.bases.'.$state)),
            IconColumn::make('is_default')->label(__('progression.default_rule'))->boolean(),
        ])->recordActions([
            EditAction::make()->using(
                fn (YearLevelRule $record, array $data) => ShowsActionErrors::run(
                    fn () => app(ManageYearLevels::class)->saveRule($record, $data, auth()->user()),
                )
            ),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageYearLevelRules::route('/')];
    }
}
