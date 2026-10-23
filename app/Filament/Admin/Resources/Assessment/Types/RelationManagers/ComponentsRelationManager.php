<?php

namespace App\Filament\Admin\Resources\Assessment\Types\RelationManagers;

use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Assessment\AssessmentComponent;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * The scored areas of a kind. A kind without areas is scored by one total (or pass/fail).
 */
class ComponentsRelationManager extends RelationManager
{
    protected static string $relationship = 'components';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('assessment.fields.components');
    }

    public function isReadOnly(): bool
    {
        return ! Gate::allows('update', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(32)->alphaDash()
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('assessment_type_id', $this->getOwnerRecord()->getKey()))
                ->helperText(__('assessment.help.component_code')),
            TextInput::make('max_score')->label(__('assessment.fields.max_score'))->numeric()->minValue(0)->required(),
            TextInput::make('min_score')->label(__('assessment.fields.min_score'))->numeric()->minValue(0)->helperText(__('assessment.help.min_score')),
            TranslatableFields::make('name', __('reference.fields.name'))->columnSpanFull(),
            TextInput::make('sort_order')->label(__('reference.fields.sort_order'))->integer()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code')),
                TextColumn::make('name')->label(__('reference.fields.name')),
                TextColumn::make('max_score')->label(__('assessment.fields.max_score')),
                TextColumn::make('min_score')->label(__('assessment.fields.min_score'))->placeholder('-'),
            ])
            ->defaultSort('sort_order')
            ->paginated(false)
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()->mutateRecordDataUsing(fn (array $data, AssessmentComponent $record) => [...$data, 'name' => $record->getTranslations('name')]), DeleteAction::make()]);
    }
}
