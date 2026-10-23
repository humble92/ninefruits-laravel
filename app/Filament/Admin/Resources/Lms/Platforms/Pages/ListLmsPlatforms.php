<?php

namespace App\Filament\Admin\Resources\Lms\Platforms\Pages;

use App\Filament\Admin\Resources\Lms\Platforms\LmsPlatformResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLmsPlatforms extends ListRecords
{
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    protected static string $resource = LmsPlatformResource::class;
}
