<?php

namespace App\Filament\Admin\Resources\Grading\GradeChanges;

use App\Domain\Grading\Actions\ManageGradeChanges;
use App\Domain\Grading\Enums\GradeChangeStatus;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Grading\GradeChanges\Pages\ListGradeChangeRequests;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Grading\GradeChangeRequest;
use App\Models\Offering\Section;
use App\Models\Person\Person;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Grade change requests (legacy aca1/student_scoreedit*, which changed grades directly).
 * Someone other than the requester approves; approval applies the change.
 */
class GradeChangeRequestResource extends Resource
{
    protected static ?string $model = GradeChangeRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Grading;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'grading/changes';

    public static function getModelLabel(): string
    {
        return __('grading.resources.grade_changes.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('grading.resources.grade_changes.plural_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = self::getEloquentQuery()->where('status', GradeChangeStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * Requests of sections in the user's scope, and the user's own.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = self::actor();

        return parent::getEloquentQuery()->where(fn (Builder $query) => $query
            ->where('requested_by_id', $user->id)
            ->orWhereHas('enrollment.section', fn (Builder $section) => Section::constrainToScope($section, $user, Permission::GradeChangesApprove, Permission::GradeChangesRequest, Permission::GradesView)));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['enrollment.studentRecord.person', 'enrollment.section.course', 'requestedBy', 'decidedBy']))
            ->columns([
                TextColumn::make('requested_at')->label(__('grading.fields.requested_at'))->dateTime()->sortable(),
                TextColumn::make('enrollment.studentRecord.student_number')->label(__('people.fields.student_number'))->searchable(),
                TextColumn::make('name')->label(__('people.fields.name'))->state(fn (GradeChangeRequest $record) => $record->enrollment->studentRecord->person->displayName())
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('enrollment.studentRecord.person', fn (Builder $person) => Person::constrainToSearch($person, $search))),
                TextColumn::make('section')->label(__('offering.fields.section'))->state(fn (GradeChangeRequest $record) => $record->enrollment->section->label()),
                TextColumn::make('change')->label(__('grading.fields.change'))->state(fn (GradeChangeRequest $record) => ($record->from_grade_code ?? '-').' → '.$record->to_grade_code),
                TextColumn::make('reason')->label(__('common.reason'))->limit(50)->tooltip(fn (GradeChangeRequest $record) => $record->reason)->wrap(),
                TextColumn::make('requestedBy.email')->label(__('grading.fields.requested_by')),
                TextColumn::make('status')->label(__('registration.fields.status'))->formatStateUsing(fn (GradeChangeStatus $state) => $state->label())->badge()
                    ->color(fn (GradeChangeStatus $state) => match ($state) {
                        GradeChangeStatus::Pending => 'warning',
                        GradeChangeStatus::Approved => 'success',
                        GradeChangeStatus::Rejected => 'danger',
                        GradeChangeStatus::Cancelled => 'gray',
                    }),
                TextColumn::make('decidedBy.email')->label(__('grading.fields.decided_by'))->placeholder('-')
                    ->description(fn (GradeChangeRequest $record) => $record->decision_note)->toggleable(),
            ])
            ->defaultSort('requested_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label(__('registration.fields.status'))
                    ->options(collect(GradeChangeStatus::cases())->mapWithKeys(fn (GradeChangeStatus $status) => [$status->value => $status->label()]))
                    ->default(GradeChangeStatus::Pending->value),
            ])
            ->recordActions([
                Action::make('approve')->label(__('grading.actions.approve'))->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (GradeChangeRequest $record) => app(ManageGradeChanges::class)->canDecide($record, self::actor()))
                    ->modalDescription(fn (GradeChangeRequest $record) => __('grading.help.approve', ['from' => $record->from_grade_code ?? '-', 'to' => $record->to_grade_code]))
                    ->schema([Textarea::make('note')->label(__('grading.fields.decision_note'))->maxLength(1000)])
                    ->action(fn (GradeChangeRequest $record, array $data) => ShowsActionErrors::run(fn () => app(ManageGradeChanges::class)->approve($record, self::actor(), $data['note'] ?? null))),
                Action::make('reject')->label(__('grading.actions.reject'))->icon(Heroicon::OutlinedXMark)->color('danger')
                    ->visible(fn (GradeChangeRequest $record) => app(ManageGradeChanges::class)->canDecide($record, self::actor()))
                    ->schema([Textarea::make('note')->label(__('grading.fields.decision_note'))->required()->maxLength(1000)])
                    ->action(fn (GradeChangeRequest $record, array $data) => ShowsActionErrors::run(fn () => app(ManageGradeChanges::class)->reject($record, self::actor(), $data['note']))),
                Action::make('cancel')->label(__('grading.actions.cancel_request'))->color('gray')
                    ->visible(fn (GradeChangeRequest $record) => $record->status === GradeChangeStatus::Pending && $record->requested_by_id === self::actor()->id)
                    ->requiresConfirmation()
                    ->action(fn (GradeChangeRequest $record) => app(ManageGradeChanges::class)->cancel($record, self::actor())),
            ]);
    }

    public static function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    public static function getPages(): array
    {
        return ['index' => ListGradeChangeRequests::route('/')];
    }
}
