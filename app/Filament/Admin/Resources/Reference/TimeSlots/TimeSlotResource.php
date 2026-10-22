<?php

namespace App\Filament\Admin\Resources\Reference\TimeSlots;

use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Filament\Admin\Resources\Reference\TimeSlots\Pages\ManageTimeSlots;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Term\TimeSlot;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TimeSlotResource extends ReferenceResource
{
    protected static ?string $model = TimeSlot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 44;

    protected static function labelKey(): string
    {
        return 'time_slots';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(64)->unique(ignoreRecord: true),
            TranslatableFields::make('name', __('reference.fields.name')),
            TimePicker::make('start_time')->label(__('reference.fields.start_time'))->seconds(false)->required(),
            TimePicker::make('end_time')->label(__('reference.fields.end_time'))->seconds(false)->required()->after('start_time'),
            CheckboxList::make('weekdays')->label(__('reference.fields.weekdays'))
                ->options(fn () => collect(range(1, 7))->mapWithKeys(fn (int $day) => [$day => now()->startOfWeek()->addDays($day - 1)->isoFormat('ddd')]))
                ->columns(7)
                ->required(),
            TextInput::make('sort_order')->label(__('reference.fields.sort_order'))->integer()->default(0),
            Toggle::make('is_active')->label(__('reference.fields.is_active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('reference.fields.name'))->searchable(query: fn ($query, string $search) => $query->whereRaw('util.i18n_search_text(name) ILIKE ?', ['%'.$search.'%'])),
                TextColumn::make('start_time')->label(__('reference.fields.start_time'))->time('H:i'),
                TextColumn::make('end_time')->label(__('reference.fields.end_time'))->time('H:i'),
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
            'index' => ManageTimeSlots::route('/'),
        ];
    }
}
