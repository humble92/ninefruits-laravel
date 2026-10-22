<?php

namespace App\Filament\Admin\Resources\Reference\TermTypes\Pages;

use App\Filament\Admin\Resources\Reference\TermTypes\TermTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTermTypes extends ManageRecords
{
    protected static string $resource = TermTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
