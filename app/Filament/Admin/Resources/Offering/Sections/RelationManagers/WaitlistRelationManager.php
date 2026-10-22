<?php

namespace App\Filament\Admin\Resources\Offering\Sections\RelationManagers;

use App\Domain\Registration\Actions\LeaveWaitlist;
use App\Domain\Registration\Actions\PromoteWaitlist;
use App\Domain\Registration\Enums\WaitlistStatus;
use App\Filament\Admin\Resources\Registration\Enrollments\EnrollmentResource;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Offering\Section;
use App\Models\Registration\WaitlistEntry;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The waitlist in request order. Freed seats promote the first students automatically
 * (all rules checked again); staff can promote now or remove an entry with a reason.
 */
class WaitlistRelationManager extends RelationManager
{
    protected static string $relationship = 'waitlistEntries';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('registration.resources.waitlist.plural_label');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Section && ($ownerRecord->waitlist_capacity > 0 || $ownerRecord->waitlistEntries()->exists());
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        /** @var Section $section */
        $section = $this->getOwnerRecord();
        $manages = fn () => $section->isRegistrationManageableBy(EnrollmentResource::actor());

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['studentRecord.person', 'section']))
            ->columns([
                TextColumn::make('requested_at')->label(__('registration.fields.requested_at'))->dateTime('Y-m-d H:i:s')->sortable(),
                TextColumn::make('studentRecord.student_number')->label(__('people.fields.student_number')),
                TextColumn::make('name')->label(__('people.fields.name'))->state(fn (WaitlistEntry $record) => $record->studentRecord->person->displayName()),
                TextColumn::make('status')->label(__('registration.fields.status'))->formatStateUsing(fn (WaitlistStatus $state) => $state->label())->badge(),
                TextColumn::make('outcome_note')->label(__('registration.fields.outcome_note'))->placeholder('-')->wrap(),
            ])
            ->defaultSort('requested_at')
            ->filters([
                SelectFilter::make('status')->label(__('registration.fields.status'))
                    ->options(collect(WaitlistStatus::cases())->mapWithKeys(fn (WaitlistStatus $status) => [$status->value => $status->label()]))
                    ->default(WaitlistStatus::Waiting->value),
            ])
            ->headerActions([
                Action::make('promote')->label(__('registration.actions.promote_waitlist'))->icon(Heroicon::OutlinedArrowUpCircle)
                    ->visible($manages)->requiresConfirmation()
                    ->action(function (PromoteWaitlist $promote) use ($section) {
                        Notification::make()->success()->title(__('registration.notifications.promoted', $promote->handle($section)))->send();
                    }),
            ])
            ->recordActions([
                Action::make('remove')->label(__('registration.actions.remove_from_waitlist'))->color('danger')->icon(Heroicon::OutlinedXMark)
                    ->visible(fn (WaitlistEntry $record) => $record->status === WaitlistStatus::Waiting && $manages())
                    ->schema([Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000)])
                    ->action(fn (WaitlistEntry $record, array $data) => ShowsActionErrors::run(fn () => app(LeaveWaitlist::class)->handle($record, EnrollmentResource::actor(), $data['reason']))),
            ]);
    }
}
