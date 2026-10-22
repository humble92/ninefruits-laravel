<?php

namespace App\Filament\Admin\Support;

/**
 * For EditRecord pages of translatable models.
 */
trait FillsTranslatableFields
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return TranslatableFormData::fill($data, $this->getRecord());
    }
}
