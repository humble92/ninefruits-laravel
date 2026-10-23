<?php

namespace App\Filament\Admin\Resources\Offering\Sections\RelationManagers;

use App\Domain\Attendance\Actions\GenerateClassMeetings;
use App\Domain\Attendance\Actions\ManageClassMeetings;
use App\Domain\Attendance\Enums\ClassMeetingStatus;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Attendance\ClassMeeting;
use App\Models\Offering\Section;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * The class meetings of a section with how many attendance records each has; staff
 * generate and cancel them here, the instructors in the portal.
 */
class ClassMeetingsRelationManager extends RelationManager
{
    protected static string $relationship = 'classMeetings';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('attendance.fields.meetings');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        /** @var Section $section */
        $section = $this->getOwnerRecord();
        $canEdit = $section->isManageableBy($this->actor());

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('room.building')->withCount('records'))
            ->columns([
                TextColumn::make('meeting_date')->label(__('attendance.fields.meeting_date'))->date()->sortable(),
                TextColumn::make('time')->label(__('attendance.fields.time'))->state(fn (ClassMeeting $record) => $record->timeLabel()),
                TextColumn::make('room.code')->label(__('reference.resources.rooms.label'))->placeholder('-')
                    ->formatStateUsing(fn (string $state, ClassMeeting $record) => $record->room?->building->code.'-'.$state),
                TextColumn::make('status')->label(__('registration.fields.status'))->badge()->formatStateUsing(fn (ClassMeetingStatus $state) => $state->label())
                    ->color(fn (ClassMeetingStatus $state) => $state === ClassMeetingStatus::Scheduled ? 'success' : 'gray'),
                TextColumn::make('records_count')->label(__('attendance.fields.recorded')),
                TextColumn::make('cancel_reason')->label(__('common.reason'))->placeholder('-')->limit(40),
            ])
            ->defaultSort('meeting_date')
            ->paginated([25, 50])
            ->headerActions([
                Action::make('generate')->label(__('attendance.actions.generate'))->icon(Heroicon::OutlinedCalendarDays)->visible($canEdit)->authorize($canEdit)
                    ->requiresConfirmation()->modalDescription(__('attendance.help.generate'))
                    ->action(function (GenerateClassMeetings $generate) use ($section) {
                        $created = $generate->forSection($section, $this->actor());
                        Notification::make()->success()->title(__('attendance.portal.generated', ['count' => $created]))->send();
                    }),
            ])
            ->recordActions([
                Action::make('cancel')->label(__('attendance.portal.cancel'))->icon(Heroicon::OutlinedXMark)->color('danger')
                    ->visible(fn (ClassMeeting $record) => $canEdit && $record->status === ClassMeetingStatus::Scheduled)
                    ->schema([Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(500)])
                    ->action(fn (ClassMeeting $record, array $data, ManageClassMeetings $manage) => ShowsActionErrors::run(fn () => $manage->cancel($record, $this->actor(), $data['reason']))),
                Action::make('restore')->label(__('attendance.portal.restore'))->icon(Heroicon::OutlinedArrowUturnLeft)->color('gray')
                    ->visible(fn (ClassMeeting $record) => $canEdit && $record->status === ClassMeetingStatus::Cancelled)
                    ->action(fn (ClassMeeting $record, ManageClassMeetings $manage) => ShowsActionErrors::run(fn () => $manage->restore($record, $this->actor()))),
            ]);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
