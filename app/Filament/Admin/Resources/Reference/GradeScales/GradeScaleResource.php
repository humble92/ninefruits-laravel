<?php

namespace App\Filament\Admin\Resources\Reference\GradeScales;

use App\Filament\Admin\Resources\Reference\GradeScales\Pages\CreateGradeScale;
use App\Filament\Admin\Resources\Reference\GradeScales\Pages\EditGradeScale;
use App\Filament\Admin\Resources\Reference\GradeScales\Pages\ListGradeScales;
use App\Filament\Admin\Resources\Reference\GradeScales\RelationManagers\ItemsRelationManager;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Grading\GradeScale;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GradeScaleResource extends ReferenceResource
{
    protected static ?string $model = GradeScale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 61;

    protected static function labelKey(): string
    {
        return 'grade_scales';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(32)->unique(ignoreRecord: true),
            TranslatableFields::make('name', __('reference.fields.name')),
            TextInput::make('max_grade_points')->label(__('reference.fields.max_grade_points'))->numeric()->minValue(0)->maxValue(99.99)->required(),
            Toggle::make('is_default')->label(__('reference.fields.is_default'))->helperText(__('reference.help.is_default_scale')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('reference.fields.name')),
                TextColumn::make('max_grade_points')->label(__('reference.fields.max_grade_points')),
                TextColumn::make('items_count')->label(__('reference.fields.items_count'))->counts('items'),
                IconColumn::make('is_default')->label(__('reference.fields.is_default'))->boolean(),
            ])
            ->defaultSort('code')
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [ItemsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGradeScales::route('/'),
            'create' => CreateGradeScale::route('/create'),
            'edit' => EditGradeScale::route('/{record}/edit'),
        ];
    }
}
