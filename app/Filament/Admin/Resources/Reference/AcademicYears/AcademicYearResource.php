<?php

namespace App\Filament\Admin\Resources\Reference\AcademicYears;

use App\Filament\Admin\Resources\Reference\AcademicYears\Pages\ManageAcademicYears;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Term\AcademicYear;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AcademicYearResource extends ReferenceResource
{
    protected static ?string $model = AcademicYear::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?int $navigationSort = 40;

    protected static function labelKey(): string
    {
        return 'academic_years';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(64)->unique(ignoreRecord: true),
            TranslatableFields::make('name', __('reference.fields.name')),
            DatePicker::make('start_date')->label(__('reference.fields.start_date'))->required(),
            DatePicker::make('end_date')->label(__('reference.fields.end_date'))->required()->after('start_date'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('reference.fields.name'))->searchable(query: fn ($query, string $search) => $query->whereRaw('util.i18n_search_text(name) ILIKE ?', ['%'.$search.'%'])),
                TextColumn::make('start_date')->label(__('reference.fields.start_date'))->date()->sortable(),
                TextColumn::make('end_date')->label(__('reference.fields.end_date'))->date(),
            ])
            ->defaultSort('start_date')
            ->recordActions([
                EditAction::make(),
                SafeDeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAcademicYears::route('/'),
        ];
    }
}
