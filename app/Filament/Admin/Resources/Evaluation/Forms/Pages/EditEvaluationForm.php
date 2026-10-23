<?php

namespace App\Filament\Admin\Resources\Evaluation\Forms\Pages;

use App\Domain\Evaluation\Actions\CopyEvaluationForm;
use App\Filament\Admin\Resources\Evaluation\Forms\EvaluationFormResource;
use App\Filament\Admin\Support\FillsTranslatableFields;
use App\Models\Evaluation\EvaluationForm;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class EditEvaluationForm extends EditRecord
{
    use FillsTranslatableFields;

    protected static string $resource = EvaluationFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('copy')->label(__('evaluation.actions.copy_form'))->icon(Heroicon::OutlinedDocumentDuplicate)->color('gray')
                ->authorize(fn () => Gate::allows('create', EvaluationForm::class))
                ->schema([TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(32)->alphaDash()->unique('evaluation_forms', 'code')])
                ->action(function (EvaluationForm $record, array $data, CopyEvaluationForm $copy) {
                    /** @var User $user */
                    $user = Auth::user();
                    $this->redirect(EvaluationFormResource::getUrl('edit', ['record' => $copy->handle($record, $data['code'], $user)]));
                }),
        ];
    }
}
