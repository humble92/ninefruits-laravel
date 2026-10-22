<?php

namespace App\Filament\Admin\Imports;

use App\Models\Facility\Building;
use App\Models\Facility\Room;
use App\Models\Institution\LookupValue;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

/**
 * Columns: building (code), code (room number), name_{locale}, room_type (code of the
 * room_type code list), org_unit (code), capacity, is_schedulable, is_active.
 */
class RoomImporter extends ReferenceImporter
{
    protected static ?string $model = Room::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('building')->requiredMapping()->rules(['required'])
                ->fillRecordUsing(fn () => null),
            ImportColumn::make('code')->requiredMapping()->rules(['required', 'max:32']),
            ...self::translatableColumns('name', required: false),
            ImportColumn::make('room_type')
                ->fillRecordUsing(function (Room $record, ?string $state): void {
                    if (blank($state)) {
                        return;
                    }

                    $type = LookupValue::query()->ofType(Room::ROOM_TYPE_LOOKUP)->where('code', $state)->first();

                    if ($type === null) {
                        throw new RowImportFailedException(__('reference.import.unknown_code', ['column' => 'room_type', 'code' => $state]));
                    }

                    $record->room_type_id = $type->id;
                }),
            ImportColumn::make('org_unit')->relationship('orgUnit', 'code'),
            ImportColumn::make('capacity')->integer()->rules(['nullable', 'integer', 'min:0']),
            ImportColumn::make('is_schedulable')->boolean()->rules(['nullable', 'boolean']),
            ImportColumn::make('is_active')->boolean()->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): Room
    {
        $building = Building::query()->where('code', $this->data['building'])->first();

        if ($building === null) {
            throw new RowImportFailedException(__('reference.import.unknown_code', ['column' => 'building', 'code' => $this->data['building']]));
        }

        return Room::query()->firstOrNew(['building_id' => $building->id, 'code' => $this->data['code']]);
    }

    public static function getCompletedNotificationTitle(Import $import): string
    {
        return __('reference.import.title', ['type' => __('reference.resources.rooms.plural_label')]);
    }
}
