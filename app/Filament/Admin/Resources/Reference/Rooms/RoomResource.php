<?php

namespace App\Filament\Admin\Resources\Reference\Rooms;

use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Filament\Admin\Resources\Reference\Rooms\Pages\ManageRooms;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Facility\Building;
use App\Models\Facility\Room;
use App\Models\Institution\LookupValue;
use App\Models\Organization\OrgUnit;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RoomResource extends ReferenceResource
{
    protected static ?string $model = Room::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?int $navigationSort = 31;

    protected static function labelKey(): string
    {
        return 'rooms';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('building_id')->label(__('reference.fields.building'))
                ->relationship('building', 'code')
                ->getOptionLabelFromRecordUsing(fn (Building $record) => $record->code.' '.$record->name)
                ->preload()
                ->required(),
            TextInput::make('code')->label(__('reference.fields.room_number'))->required()->maxLength(32)
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule, Get $get) => $rule->where('building_id', $get('building_id'))),
            TranslatableFields::make('name', __('reference.fields.name'), required: false),
            Select::make('room_type_id')->label(__('reference.fields.room_type'))
                ->options(fn () => LookupValue::query()->ofType(Room::ROOM_TYPE_LOOKUP)->where('is_active', true)->orderBy('sort_order')->get()->mapWithKeys(fn (LookupValue $value) => [$value->id => $value->name])),
            Select::make('org_unit_id')->label(__('reference.fields.org_unit'))
                ->relationship('orgUnit', 'code')
                ->getOptionLabelFromRecordUsing(fn (OrgUnit $record) => $record->code.' '.$record->name)
                ->searchable(['code'])
                ->preload(),
            TextInput::make('capacity')->label(__('reference.fields.capacity'))->integer()->minValue(0),
            Toggle::make('is_schedulable')->label(__('reference.fields.is_schedulable'))->helperText(__('reference.help.is_schedulable'))->default(true),
            Toggle::make('is_active')->label(__('reference.fields.is_active'))->default(true),
            Textarea::make('note')->label(__('reference.fields.note'))->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['building', 'roomType']))
            ->columns([
                TextColumn::make('building.code')->label(__('reference.fields.building'))->sortable(),
                TextColumn::make('code')->label(__('reference.fields.room_number'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('reference.fields.name'))->placeholder('-'),
                TextColumn::make('roomType.name')->label(__('reference.fields.room_type'))->placeholder('-'),
                TextColumn::make('capacity')->label(__('reference.fields.capacity'))->sortable()->placeholder('-'),
                IconColumn::make('is_schedulable')->label(__('reference.fields.is_schedulable'))->boolean(),
                IconColumn::make('is_active')->label(__('reference.fields.is_active'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('building_id')->label(__('reference.fields.building'))->relationship('building', 'code'),
                TernaryFilter::make('is_active')->label(__('reference.fields.is_active')),
            ])
            ->defaultSort('code')
            ->recordActions([
                EditAction::make(),
                SafeDeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRooms::route('/'),
        ];
    }
}
