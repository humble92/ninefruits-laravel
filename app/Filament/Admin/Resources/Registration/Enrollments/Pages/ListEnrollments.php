<?php

namespace App\Filament\Admin\Resources\Registration\Enrollments\Pages;

use App\Filament\Admin\Resources\Registration\Enrollments\EnrollmentResource;
use Filament\Resources\Pages\ListRecords;

class ListEnrollments extends ListRecords
{
    protected static string $resource = EnrollmentResource::class;
}
