<?php

namespace App\Filament\Admin\Resources\Evaluation\Forms;

use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Evaluation\Forms\Pages\CreateEvaluationForm;
use App\Filament\Admin\Resources\Evaluation\Forms\Pages\EditEvaluationForm;
use App\Filament\Admin\Resources\Evaluation\Forms\Pages\ListEvaluationForms;
use App\Filament\Admin\Resources\Evaluation\Forms\RelationManagers\QuestionsRelationManager;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Evaluation\EvaluationForm;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Course evaluation questionnaires (legacy SURCATEGORY, SURCONTENT: no screen, loaded by
 * SQL each term). A form with responses keeps its questions; copy it to change them.
 */
class EvaluationFormResource extends Resource
{
    protected static ?string $model = EvaluationForm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Evaluation;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'evaluation/forms';

    public static function getModelLabel(): string
    {
        return __('evaluation.resources.forms.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('evaluation.resources.forms.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make()->columns(2)->schema([
                TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(32)->alphaDash()->unique(ignoreRecord: true),
                Toggle::make('is_active')->label(__('reference.fields.is_active'))->default(true),
                TranslatableFields::make('name', __('reference.fields.name'))->columnSpanFull(),
                TranslatableFields::make('description', __('evaluation.fields.description'), required: false)->columnSpanFull(),
                TextInput::make('scale_points')->label(__('evaluation.fields.scale_points'))->integer()->minValue(2)->maxValue(10)->default(5)->required()
                    ->disabled(fn (?EvaluationForm $record) => $record?->isLocked() ?? false),
            ]),
            TranslatableFields::make('scale_low_label', __('evaluation.fields.scale_low_label'), required: false),
            TranslatableFields::make('scale_high_label', __('evaluation.fields.scale_high_label'), required: false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable(),
                TextColumn::make('name')->label(__('reference.fields.name')),
                TextColumn::make('questions_count')->label(__('evaluation.fields.questions'))->counts('questions'),
                TextColumn::make('scale_points')->label(__('evaluation.fields.scale_points')),
                IconColumn::make('is_active')->label(__('reference.fields.is_active'))->boolean(),
            ])
            ->defaultSort('code')
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [QuestionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvaluationForms::route('/'),
            'create' => CreateEvaluationForm::route('/create'),
            'edit' => EditEvaluationForm::route('/{record}/edit'),
        ];
    }
}
