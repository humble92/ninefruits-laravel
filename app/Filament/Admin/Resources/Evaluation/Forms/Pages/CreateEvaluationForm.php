<?php

namespace App\Filament\Admin\Resources\Evaluation\Forms\Pages;

use App\Filament\Admin\Resources\Evaluation\Forms\EvaluationFormResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEvaluationForm extends CreateRecord
{
    protected static string $resource = EvaluationFormResource::class;
}
