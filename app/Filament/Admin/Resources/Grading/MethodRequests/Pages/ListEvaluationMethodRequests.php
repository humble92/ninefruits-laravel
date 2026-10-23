<?php

namespace App\Filament\Admin\Resources\Grading\MethodRequests\Pages;

use App\Filament\Admin\Resources\Grading\MethodRequests\EvaluationMethodRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListEvaluationMethodRequests extends ListRecords
{
    protected static string $resource = EvaluationMethodRequestResource::class;
}
