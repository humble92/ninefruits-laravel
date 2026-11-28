<?php

namespace App\Filament\Admin\Resources\StudentRecords\YearLevelRules;

use App\Domain\Person\Actions\ManageYearLevels;
use App\Filament\Admin\Support\ShowsActionErrors;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageYearLevelRules extends ManageRecords
{
    protected static string $resource = YearLevelRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->using(
                fn (array $data) => ShowsActionErrors::run(
                    fn () => app(ManageYearLevels::class)->saveRule(null, $data, auth()->user()),
                )
            ),
        ];
    }
}
