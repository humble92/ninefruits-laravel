<?php

namespace App\Filament\Admin\Resources\Lms\Platforms\Pages;

use App\Filament\Admin\Resources\Lms\Platforms\LmsPlatformResource;
use App\Filament\Admin\Support\FillsTranslatableFields;
use Filament\Resources\Pages\EditRecord;

class EditLmsPlatform extends EditRecord
{
    use FillsTranslatableFields {
        mutateFormDataBeforeFill as fillTranslatable;
    }

    protected static string $resource = LmsPlatformResource::class;

    /**
     * Credentials are never filled into the form; an empty field keeps the stored ones.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = $this->fillTranslatable($data);
        unset($data['credentials']);

        return $data;
    }
}
