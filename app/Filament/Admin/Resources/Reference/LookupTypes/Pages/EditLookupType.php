<?php

namespace App\Filament\Admin\Resources\Reference\LookupTypes\Pages;

use App\Filament\Admin\Resources\Reference\LookupTypes\LookupTypeResource;
use App\Filament\Admin\Support\FillsTranslatableFields;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Models\Institution\LookupType;
use Filament\Resources\Pages\EditRecord;

class EditLookupType extends EditRecord
{
    use FillsTranslatableFields;

    protected static string $resource = LookupTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SafeDeleteAction::make()->hidden(fn (LookupType $record) => $record->is_system),
        ];
    }
}
