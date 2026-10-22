<?php

namespace App\Filament\Admin\Resources\StudentRecords\Pages;

use App\Filament\Admin\Resources\StudentRecords\StudentRecordResource;
use Filament\Resources\Pages\EditRecord;

class EditStudentRecord extends EditRecord
{
    protected static string $resource = StudentRecordResource::class;

    protected function getRedirectUrl(): string
    {
        return StudentRecordResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
