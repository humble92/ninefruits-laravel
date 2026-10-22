<?php

namespace App\Filament\Admin\Resources\Reference\CourseCategories;

use App\Filament\Admin\Resources\Reference\CourseCategories\Pages\ManageCourseCategories;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Curriculum\CourseCategory;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseCategoryResource extends ReferenceResource
{
    protected static ?string $model = CourseCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 60;

    protected static function labelKey(): string
    {
        return 'course_categories';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(64)->unique(ignoreRecord: true),
            TranslatableFields::make('name', __('reference.fields.name')),
            Select::make('parent_id')->label(__('reference.fields.parent'))
                ->relationship('parent', 'code', fn ($query, ?CourseCategory $record) => $record === null ? $query : $query->whereKeyNot($record->getKey()))
                ->getOptionLabelFromRecordUsing(fn (CourseCategory $record) => $record->code.' '.$record->name),
            TextInput::make('sort_order')->label(__('reference.fields.sort_order'))->integer()->default(0),
            Toggle::make('is_active')->label(__('reference.fields.is_active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['parent']))
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('reference.fields.name'))->searchable(query: fn ($query, string $search) => $query->whereRaw('util.i18n_search_text(name) ILIKE ?', ['%'.$search.'%'])),
                TextColumn::make('parent.name')->label(__('reference.fields.parent'))->placeholder('-'),
                TextColumn::make('sort_order')->label(__('reference.fields.sort_order'))->sortable(),
                IconColumn::make('is_active')->label(__('reference.fields.is_active'))->boolean(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
                SafeDeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCourseCategories::route('/'),
        ];
    }
}
