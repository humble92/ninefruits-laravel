<?php

namespace App\Filament\Admin\Resources\Grading\Sections;

use App\Domain\Grading\Actions\FinalizeGrades;
use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Grading\Sections\Pages\ListSectionGrades;
use App\Filament\Admin\Resources\Offering\Sections\SectionResource;
use App\Filament\Admin\Resources\Reference\OrgUnits\OrgUnitResource;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Offering\Section;
use App\Models\Person\Instructor;
use App\Models\Term\Term;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * Grade progress per section for staff: how many grades are entered, submitted and final,
 * finalizing submitted grades and returning them to the instructors.
 */
class SectionGradeResource extends Resource
{
    protected static ?string $model = Section::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Grading;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'grading/sections';

    public static function getModelLabel(): string
    {
        return __('grading.resources.section_grades.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('grading.resources.section_grades.plural_label');
    }

    public static function canViewAny(): bool
    {
        return self::actor()->can(Permission::GradesView->value) || self::actor()->can(Permission::GradesManage->value);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->whereHas('enrollments', fn (Builder $enrollment) => $enrollment->whereIn('status', [EnrollmentStatus::Enrolled, EnrollmentStatus::Completed]));
        Section::constrainToScope($query, self::actor(), Permission::GradesView, Permission::GradesManage);

        return $query;
    }

    public static function table(Table $table): Table
    {
        $count = fn (string $alias, array $statuses) => ["enrollments as {$alias}" => fn (Builder $enrollment) => $enrollment
            ->whereIn('status', [EnrollmentStatus::Enrolled, EnrollmentStatus::Completed])->whereIn('grade_status', $statuses)];

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['course', 'version', 'instructors.person'])
                ->withCount([
                    ...$count('pending_count', [GradeStatus::None, GradeStatus::Draft]),
                    ...$count('submitted_count', [GradeStatus::Submitted]),
                    ...$count('finalized_count', [GradeStatus::Finalized]),
                ]))
            ->columns([
                TextColumn::make('label')->label(__('offering.fields.section'))->state(fn (Section $record) => $record->label())
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('course', fn (Builder $course) => $course->where('code', 'ilike', $search.'%'))),
                TextColumn::make('version.title')->label(__('curriculum.fields.title')),
                TextColumn::make('instructors')->label(__('offering.fields.instructors'))
                    ->state(fn (Section $record) => $record->instructors->map(fn (Instructor $instructor) => $instructor->person->displayName())->implode(', ') ?: '-'),
                TextColumn::make('pending_count')->label(__('grading.fields.pending'))->color(fn (int $state) => $state > 0 ? 'warning' : null),
                TextColumn::make('submitted_count')->label(__('grading.fields.submitted')),
                TextColumn::make('finalized_count')->label(__('grading.fields.finalized')),
            ])
            ->defaultSort('course_id')
            ->filters([
                SelectFilter::make('term_id')->label(__('reference.resources.terms.label'))
                    ->options(fn () => Term::query()->orderByDesc('start_date')->get()->mapWithKeys(fn (Term $term) => [$term->id => $term->name.' ('.$term->code.')']))
                    ->default(fn () => SectionResource::defaultTermId()),
                SelectFilter::make('org_unit_id')->label(__('offering.fields.offering_unit'))->options(fn () => OrgUnitResource::unitOptions()),
                TernaryFilter::make('ready')->label(__('grading.fields.ready_to_finalize'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('enrollments', fn (Builder $enrollment) => $enrollment->where('grade_status', GradeStatus::Submitted))
                            ->whereDoesntHave('enrollments', fn (Builder $enrollment) => $enrollment->where('status', EnrollmentStatus::Enrolled)->whereIn('grade_status', [GradeStatus::None, GradeStatus::Draft])),
                        false: fn (Builder $query) => $query->whereHas('enrollments', fn (Builder $enrollment) => $enrollment->where('status', EnrollmentStatus::Enrolled)->whereIn('grade_status', [GradeStatus::None, GradeStatus::Draft])),
                    ),
            ])
            ->recordActions([
                Action::make('grades')->label(__('grading.actions.open_entry'))->icon(Heroicon::OutlinedPencilSquare)->color('gray')
                    ->url(fn (Section $record) => route('portal.grades.entry', $record))->openUrlInNewTab(),
                Action::make('finalize')->label(__('grading.actions.finalize'))->icon(Heroicon::OutlinedLockClosed)->color('success')
                    ->visible(fn (Section $record) => $record->getAttribute('submitted_count') > 0 && $record->isInScopeOf(self::actor(), Permission::GradesManage))
                    ->requiresConfirmation()->modalDescription(__('grading.help.finalize'))
                    ->action(fn (Section $record) => ShowsActionErrors::run(fn () => Notification::make()->success()
                        ->title(__('grading.notifications.finalized', ['count' => app(FinalizeGrades::class)->finalize($record, self::actor())]))->send())),
                Action::make('return')->label(__('grading.actions.return'))->icon(Heroicon::OutlinedArrowUturnLeft)->color('warning')
                    ->visible(fn (Section $record) => $record->getAttribute('submitted_count') > 0 && $record->isInScopeOf(self::actor(), Permission::GradesManage))
                    ->schema([Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000)])
                    ->action(fn (Section $record, array $data) => ShowsActionErrors::run(fn () => app(FinalizeGrades::class)->return($record, self::actor(), $data['reason']))),
            ])
            ->toolbarActions([
                BulkAction::make('finalizeSelected')->label(__('grading.actions.finalize_selected'))->icon(Heroicon::OutlinedLockClosed)
                    ->authorize(fn () => self::actor()->can(Permission::GradesManage->value))
                    ->requiresConfirmation()->modalDescription(__('grading.help.finalize'))
                    ->action(function (Collection $records) {
                        $finalized = 0;
                        $skipped = [];

                        foreach ($records as $section) {
                            /** @var Section $section */
                            try {
                                $finalized += app(FinalizeGrades::class)->finalize($section, self::actor());
                            } catch (ValidationException|AuthorizationException) {
                                $skipped[] = $section->label();
                            }
                        }

                        Notification::make()->success()->title(__('grading.notifications.finalized', ['count' => $finalized]))
                            ->body($skipped === [] ? null : __('grading.notifications.skipped', ['sections' => implode(', ', $skipped)]))->send();
                    }),
            ]);
    }

    public static function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    public static function getPages(): array
    {
        return ['index' => ListSectionGrades::route('/')];
    }
}
