<?php

namespace App\Filament\Admin\Resources\Evaluation\Forms\Pages;

use App\Filament\Admin\Resources\Evaluation\Forms\EvaluationFormResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEvaluationForms extends ListRecords
{
    protected static string $resource = EvaluationFormResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
