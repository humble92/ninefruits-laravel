<?php

namespace App\Filament\Admin\Resources\Assessment\Types;

use App\Domain\Assessment\Enums\AssessmentCategory;
use App\Domain\Assessment\Enums\ScoringMethod;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Assessment\Types\Pages\CreateAssessmentType;
use App\Filament\Admin\Resources\Assessment\Types\Pages\EditAssessmentType;
use App\Filament\Admin\Resources\Assessment\Types\Pages\ListAssessmentTypes;
use App\Filament\Admin\Resources\Assessment\Types\RelationManagers\ComponentsRelationManager;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Assessment\AssessmentType;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Examination kinds (legacy CET_TYPE with fixed areas, SSCORE_ADD exam numbers, COMEXAM
 * types): the institution defines the areas and the pass rule per kind.
 */
class AssessmentTypeResource extends Resource
{
    protected static ?string $model = AssessmentType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Assessments;

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'assessments/types';

    public static function getModelLabel(): string
    {
        return __('assessment.resources.types.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('assessment.resources.types.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(32)->alphaDash()->unique(ignoreRecord: true),
            Select::make('category')->label(__('assessment.fields.category'))->required()->default(AssessmentCategory::Comprehensive->value)
                ->options(collect(AssessmentCategory::cases())->mapWithKeys(fn (AssessmentCategory $case) => [$case->value => $case->label()])),
            TranslatableFields::make('name', __('reference.fields.name'))->columnSpanFull(),
            Select::make('scoring')->label(__('assessment.fields.scoring'))->required()->default(ScoringMethod::Score->value)->live()
                ->options(collect(ScoringMethod::cases())->mapWithKeys(fn (ScoringMethod $case) => [$case->value => $case->label()]))
                ->helperText(__('assessment.help.scoring')),
            TextInput::make('max_score')->label(__('assessment.fields.max_score'))->numeric()->minValue(0)->helperText(__('assessment.help.max_score')),
            TextInput::make('pass_score')->label(__('assessment.fields.pass_score'))->numeric()->minValue(0)->helperText(__('assessment.help.pass_score')),
            Toggle::make('allows_external')->label(__('assessment.fields.allows_external'))->helperText(__('assessment.help.allows_external')),
            Toggle::make('is_active')->label(__('reference.fields.is_active'))->default(true),
            TextInput::make('sort_order')->label(__('reference.fields.sort_order'))->integer()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable(),
                TextColumn::make('name')->label(__('reference.fields.name')),
                TextColumn::make('category')->label(__('assessment.fields.category'))->formatStateUsing(fn (AssessmentCategory $state) => $state->label()),
                TextColumn::make('scoring')->label(__('assessment.fields.scoring'))->formatStateUsing(fn (ScoringMethod $state) => $state->label()),
                TextColumn::make('pass_score')->label(__('assessment.fields.pass_score'))->placeholder('-')
                    ->formatStateUsing(fn (string $state, AssessmentType $record) => $state.($record->max_score !== null ? ' / '.$record->max_score : '')),
                TextColumn::make('components_count')->label(__('assessment.fields.components'))->counts('components'),
                IconColumn::make('allows_external')->label(__('assessment.fields.allows_external'))->boolean(),
                IconColumn::make('is_active')->label(__('reference.fields.is_active'))->boolean(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [ComponentsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssessmentTypes::route('/'),
            'create' => CreateAssessmentType::route('/create'),
            'edit' => EditAssessmentType::route('/{record}/edit'),
        ];
    }
}
