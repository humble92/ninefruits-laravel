<?php

namespace App\Filament\Admin\Resources\Reference\GradeScales\RelationManagers;

use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFields;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('reference.resources.grade_scale_items.plural_label');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        $max = (float) $this->getOwnerRecord()->getAttribute('max_grade_points');

        return $schema->components([
            TextInput::make('code')->label(__('reference.fields.grade'))->required()->maxLength(16)
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('grade_scale_id', $this->getOwnerRecord()->getKey())),
            TranslatableFields::make('name', __('reference.fields.name'), required: false),
            TextInput::make('grade_points')->label(__('reference.fields.grade_points'))->numeric()->minValue(0)->maxValue($max)
                ->required(fn (Get $get) => (bool) $get('counts_in_gpa')),
            TextInput::make('min_percentage')->label(__('reference.fields.min_percentage'))->numeric()->minValue(0)->maxValue(100),
            TextInput::make('max_percentage')->label(__('reference.fields.max_percentage'))->numeric()->minValue(0)->maxValue(100)->gte('min_percentage'),
            Toggle::make('counts_in_gpa')->label(__('reference.fields.counts_in_gpa'))->default(true)->live(),
            Toggle::make('is_passing')->label(__('reference.fields.is_passing'))->default(true),
            TextInput::make('sort_order')->label(__('reference.fields.sort_order'))->integer()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.grade')),
                TextColumn::make('grade_points')->label(__('reference.fields.grade_points'))->placeholder('-'),
                TextColumn::make('min_percentage')->label(__('reference.fields.min_percentage'))->placeholder('-'),
                TextColumn::make('max_percentage')->label(__('reference.fields.max_percentage'))->placeholder('-'),
                IconColumn::make('counts_in_gpa')->label(__('reference.fields.counts_in_gpa'))->boolean(),
                IconColumn::make('is_passing')->label(__('reference.fields.is_passing'))->boolean(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), SafeDeleteAction::make()]);
    }
}
