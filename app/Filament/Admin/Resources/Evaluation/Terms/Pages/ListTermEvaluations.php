<?php

namespace App\Filament\Admin\Resources\Evaluation\Terms\Pages;

use App\Filament\Admin\Resources\Evaluation\Terms\TermEvaluationResource;
use Filament\Resources\Pages\ListRecords;

class ListTermEvaluations extends ListRecords
{
    protected static string $resource = TermEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [TermEvaluationResource::prepareAction('prepare')];
    }
}
