<?php

namespace App\Filament\Admin\Support;

use App\Filament\Admin\Resources\Reference\OrgUnits\OrgUnitResource;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\Program;
use App\Support\Targeting\TargetCriteria;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

/**
 * Form section for a TargetCriteria value (term windows, section eligibility and
 * exclusion, seat groups). Use with toState() when filling and fromState() when saving.
 */
final class TargetCriteriaFields
{
    public static function make(string $statePath, string $label, ?string $description = null): Section
    {
        return Section::make($label)
            ->description($description)
            ->statePath($statePath)
            ->columns(2)
            ->collapsible()
            ->schema([
                CheckboxList::make('year_levels')->label(__('reference.fields.year_levels'))
                    ->options(collect(range(1, 6))->mapWithKeys(fn (int $level) => [$level => (string) $level])->all())->columns(6),
                TextInput::make('admission_year_from')->label(__('reference.fields.admission_year_from'))->integer()->minValue(1900)->maxValue(2200),
                TextInput::make('admission_year_to')->label(__('reference.fields.admission_year_to'))->integer()->minValue(1900)->maxValue(2200)->gte('admission_year_from'),
                Select::make('program_ids')->label(__('reference.fields.programs'))->multiple()
                    ->options(fn () => Program::query()->effectiveOn()->orderBy('code')->get()->mapWithKeys(fn (Program $program) => [$program->id => $program->code.' '.$program->name])),
                Select::make('org_unit_ids')->label(__('reference.fields.org_units'))->multiple()
                    ->options(fn () => OrgUnitResource::unitOptions())
                    ->helperText(__('reference.help.org_unit_descendants')),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function toState(TargetCriteria $criteria): array
    {
        return $criteria->toArray();
    }

    public static function fromState(mixed $state): TargetCriteria
    {
        return TargetCriteria::fromArray(is_array($state) ? $state : null);
    }

    /**
     * A short description for tables, e.g. "Year 1, 2 · Admitted 2014~ · NURS".
     */
    public static function summary(TargetCriteria $criteria): string
    {
        $years = match (true) {
            $criteria->admissionYearFrom !== null && $criteria->admissionYearTo !== null => $criteria->admissionYearFrom.'~'.$criteria->admissionYearTo,
            $criteria->admissionYearFrom !== null => $criteria->admissionYearFrom.'~',
            $criteria->admissionYearTo !== null => '~'.$criteria->admissionYearTo,
            default => null,
        };

        return implode(' · ', array_filter([
            $criteria->yearLevels === [] ? null : __('reference.fields.year_levels').' '.implode(', ', $criteria->yearLevels),
            $years === null ? null : __('reference.fields.admission_years').' '.$years,
            $criteria->programIds === [] ? null : Program::query()->whereKey($criteria->programIds)->orderBy('code')->pluck('code')->implode(', '),
            $criteria->orgUnitIds === [] ? null : OrgUnit::query()->whereKey($criteria->orgUnitIds)->orderBy('code')->pluck('code')->implode(', '),
        ]));
    }
}
