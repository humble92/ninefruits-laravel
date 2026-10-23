<?php

namespace App\Filament\Admin\Resources\Assessment\Sessions\Pages;

use App\Filament\Admin\Resources\Assessment\Sessions\AssessmentSessionResource;
use App\Models\Assessment\AssessmentSession;
use Filament\Resources\Pages\EditRecord;

class EditAssessmentSession extends EditRecord
{
    protected static string $resource = AssessmentSessionResource::class;

    public function getTitle(): string
    {
        /** @var AssessmentSession $session */
        $session = $this->getRecord();

        return $session->label();
    }

    protected function getHeaderActions(): array
    {
        return [AssessmentSessionResource::importAction(), AssessmentSessionResource::publishAction()];
    }
}
