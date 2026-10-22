<?php

namespace App\Filament\Admin\Resources\Offering\Sections\RelationManagers;

use App\Filament\Admin\Support\TargetCriteriaFields;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Offering\Section;
use App\Models\Offering\SectionSeatGroup;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Reserved seats (e.g. 30 for majors) that open to everyone at a set time. Registration
 * uses them from Phase 5.
 */
class SeatGroupsRelationManager extends RelationManager
{
    protected static string $relationship = 'seatGroups';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('offering.resources.seat_groups.plural_label');
    }

    /**
     * Only staff who manage the section change it (sections.manage in its unit's scope).
     */
    public function isReadOnly(): bool
    {
        return ! Gate::allows('update', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TranslatableFields::make('name', __('reference.fields.name'))->columnSpanFull(),
            TextInput::make('capacity')->label(__('offering.fields.capacity'))->integer()->minValue(0)->required()
                ->rule(fn (?SectionSeatGroup $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                    /** @var Section $section */
                    $section = $this->getOwnerRecord();
                    $others = $section->seatGroups()->when($record !== null, fn ($query) => $query->whereKeyNot($record->id))->sum('capacity');

                    if ($others + (int) $value > $section->capacity) {
                        $fail(__('offering.errors.seat_groups_exceed_capacity', ['capacity' => $section->capacity]));
                    }
                }),
            DateTimePicker::make('releases_at')->label(__('offering.fields.releases_at'))->seconds(false)->helperText(__('offering.help.releases_at')),
            TargetCriteriaFields::make('eligibility', __('offering.fields.seat_group_eligibility'))->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('reference.fields.name')),
                TextColumn::make('capacity')->label(__('offering.fields.capacity')),
                TextColumn::make('releases_at')->label(__('offering.fields.releases_at'))->dateTime()->placeholder('-'),
            ])
            ->headerActions([
                CreateAction::make()->mutateDataUsing(fn (array $data) => [...$data, 'eligibility' => TargetCriteriaFields::fromState($data['eligibility'] ?? null)]),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, SectionSeatGroup $record) => [...$data, 'name' => $record->getTranslations('name'), 'eligibility' => TargetCriteriaFields::toState($record->eligibility)])
                    ->mutateDataUsing(fn (array $data) => [...$data, 'eligibility' => TargetCriteriaFields::fromState($data['eligibility'] ?? null)]),
                DeleteAction::make(),
            ]);
    }
}
