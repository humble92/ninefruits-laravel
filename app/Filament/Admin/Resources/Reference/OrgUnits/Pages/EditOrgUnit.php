<?php

namespace App\Filament\Admin\Resources\Reference\OrgUnits\Pages;

use App\Domain\Organization\Actions\ReorganizeOrgUnit;
use App\Domain\Organization\Enums\OrgUnitChangeType;
use App\Filament\Admin\Resources\Reference\OrgUnits\OrgUnitResource;
use App\Filament\Admin\Support\FillsTranslatableFields;
use App\Models\Organization\OrgUnit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class EditOrgUnit extends EditRecord
{
    use FillsTranslatableFields;

    protected static string $resource = OrgUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reorganize')
                ->label(__('reference.actions.reorganize'))
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->authorize(fn (OrgUnit $record) => Gate::allows('update', $record))
                ->schema([
                    Select::make('change_type')->label(__('reference.fields.change_type'))
                        ->options(collect(OrgUnitChangeType::cases())->mapWithKeys(fn (OrgUnitChangeType $type) => [$type->value => $type->label()]))
                        ->required()
                        ->live(),
                    Select::make('successor_ids')->label(__('reference.fields.successors'))
                        ->options(fn (OrgUnit $record) => OrgUnitResource::unitOptions($record))
                        ->multiple(fn (Get $get) => $get('change_type') === OrgUnitChangeType::Split->value)
                        ->searchable()
                        ->required(),
                    DatePicker::make('effective_date')->label(__('reference.fields.reorganization_date'))
                        ->helperText(__('reference.help.reorganization_date'))
                        ->required(),
                    Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000),
                ])
                ->action(function (OrgUnit $record, array $data, ReorganizeOrgUnit $reorganize) {
                    /** @var User $actor */
                    $actor = Auth::user();
                    $ids = (array) $data['successor_ids'];

                    $reorganize->handle(
                        $record,
                        OrgUnitChangeType::from($data['change_type']),
                        OrgUnit::query()->whereKey($ids)->get()->all(),
                        CarbonImmutable::parse($data['effective_date']),
                        $data['reason'],
                        $actor,
                    );

                    $this->refreshFormData(['effective_end_date']);
                    Notification::make()->success()->title(__('reference.notifications.reorganized'))->send();
                }),
        ];
    }
}
