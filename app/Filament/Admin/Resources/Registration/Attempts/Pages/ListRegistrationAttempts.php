<?php

namespace App\Filament\Admin\Resources\Registration\Attempts\Pages;

use App\Filament\Admin\Resources\Registration\Attempts\RegistrationAttemptResource;
use Filament\Resources\Pages\ListRecords;

class ListRegistrationAttempts extends ListRecords
{
    protected static string $resource = RegistrationAttemptResource::class;
}
