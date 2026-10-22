<?php

namespace App\Filament\Admin\Resources\Offering\Sections\RelationManagers;

use App\Domain\Offering\Actions\SaveSectionMeeting;
use App\Domain\Offering\Services\Timetable;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Facility\Room;
use App\Models\Offering\Section;
use App\Models\Offering\SectionMeeting;
use App\Models\Term\TimeSlot;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Weekly meetings. A room or instructor already booked at the time is refused with the
 * name of the other section.
 */
class MeetingsRelationManager extends RelationManager
{
    protected static string $relationship = 'meetings';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('offering.resources.meetings.plural_label');
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
        return $schema->columns(3)->components([
            Select::make('weekday')->label(__('offering.fields.weekday'))->required()
                ->options(collect(range(1, 7))->mapWithKeys(fn (int $day) => [$day => Timetable::weekdayLabel($day)])),
            Select::make('time_slot')->label(__('offering.fields.time_slot'))->dehydrated(false)->live()
                ->options(fn () => TimeSlot::query()->where('is_active', true)->orderBy('sort_order')->get()->mapWithKeys(fn (TimeSlot $slot) => [$slot->id => $slot->name.' '.substr($slot->start_time, 0, 5).'–'.substr($slot->end_time, 0, 5)]))
                ->afterStateUpdated(function (Set $set, $state) {
                    $slot = TimeSlot::query()->find($state);

                    if ($slot !== null) {
                        $set('start_time', substr($slot->start_time, 0, 5));
                        $set('end_time', substr($slot->end_time, 0, 5));
                    }
                })
                ->helperText(__('offering.help.time_slot')),
            Select::make('room_id')->label(__('offering.fields.room'))->searchable()
                ->options(fn () => Room::query()->where('is_active', true)->where('is_schedulable', true)->with('building')->get()
                    ->sortBy(fn (Room $room) => $room->building->code.$room->code)
                    ->mapWithKeys(fn (Room $room) => [$room->id => $room->building->code.'-'.$room->code.($room->capacity ? ' ('.$room->capacity.')' : '')])),
            // Class times are wall-clock times, not instants: no time zone conversion (the panel
            // converts date-times to the institution time zone).
            TimePicker::make('start_time')->label(__('offering.fields.start_time'))->seconds(false)->required()->timezone((string) config('app.timezone')),
            TimePicker::make('end_time')->label(__('offering.fields.end_time'))->seconds(false)->required()->after('start_time')->timezone((string) config('app.timezone')),
            DatePicker::make('start_date')->label(__('reference.fields.start_date'))->helperText(__('offering.help.meeting_dates')),
            DatePicker::make('end_date')->label(__('reference.fields.end_date'))->afterOrEqual('start_date'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('room.building'))
            ->columns([
                TextColumn::make('weekday')->label(__('offering.fields.weekday'))->formatStateUsing(fn (int $state) => Timetable::weekdayLabel($state)),
                TextColumn::make('time')->label(__('offering.fields.time'))->state(fn (SectionMeeting $record) => $record->timeLabel()),
                TextColumn::make('room')->label(__('offering.fields.room'))->state(fn (SectionMeeting $record) => Timetable::roomLabel($record) ?: '-'),
                TextColumn::make('start_date')->label(__('reference.fields.start_date'))->date(),
                TextColumn::make('end_date')->label(__('reference.fields.end_date'))->date(),
            ])
            ->headerActions([
                CreateAction::make()->using(fn (array $data) => ShowsActionErrors::run(fn () => app(SaveSectionMeeting::class)->handle($this->section(), $data, $this->user()))),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data) => [...$data, 'start_time' => substr((string) $data['start_time'], 0, 5), 'end_time' => substr((string) $data['end_time'], 0, 5)])
                    ->using(fn (SectionMeeting $record, array $data) => ShowsActionErrors::run(fn () => app(SaveSectionMeeting::class)->handle($this->section(), $data, $this->user(), $record))),
                DeleteAction::make(),
            ]);
    }

    private function section(): Section
    {
        /** @var Section */
        return $this->getOwnerRecord();
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
