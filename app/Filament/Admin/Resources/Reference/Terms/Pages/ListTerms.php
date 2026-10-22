<?php

namespace App\Filament\Admin\Resources\Reference\Terms\Pages;

use App\Filament\Admin\Resources\Reference\Terms\TermResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTerms extends ListRecords
{
    protected static string $resource = TermResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
