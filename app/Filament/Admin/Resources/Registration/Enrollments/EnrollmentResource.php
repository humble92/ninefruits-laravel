<?php

namespace App\Filament\Admin\Resources\Registration\Enrollments;

use App\Domain\Registration\Actions\DropEnrollment;
use App\Domain\Registration\Actions\EnrollStudent;
use App\Domain\Registration\Data\EnrollOptions;
use App\Domain\Registration\Enums\EnrollmentSource;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Registration\Enums\RuleCode;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Offering\Sections\SectionResource;
use App\Filament\Admin\Resources\Registration\Enrollments\Pages\ListEnrollments;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Filament\Admin\Support\StudentRecordSelect;
use App\Models\Offering\Section;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Registration status and staff adjustments (legacy sv-semester-obj, sv-obj-rstudent).
 * Staff registrations run the same rules; rules that allow it can be waived with a reason.
 */
class EnrollmentResource extends Resource
{
    protected static ?string $model = Enrollment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Registration;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'registration/enrollments';

    public static function getModelLabel(): string
    {
        return __('registration.resources.enrollments.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('registration.resources.enrollments.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Enrollments of records the user can see, and of sections in their scope.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = self::actor();

        return parent::getEloquentQuery()->where(fn (Builder $query) => $query
            ->whereHas('studentRecord', fn (Builder $record) => StudentRecord::constrainVisibleTo($record, $user))
            ->orWhereHas('section', fn (Builder $section) => Section::constrainManageableBy($section, $user)));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['studentRecord.person', 'section.course', 'version']))
            ->columns([
                TextColumn::make('studentRecord.student_number')->label(__('people.fields.student_number'))->searchable(),
                TextColumn::make('name')->label(__('people.fields.name'))->state(fn (Enrollment $record) => $record->studentRecord->person->displayName())
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('studentRecord.person', fn (Builder $person) => Person::constrainToSearch($person, $search))),
                TextColumn::make('section')->label(__('offering.fields.section'))->state(fn (Enrollment $record) => $record->section->label())
                    ->url(fn (Enrollment $record) => Gate::allows('update', $record->section) ? SectionResource::getUrl('edit', ['record' => $record->section]) : null),
                TextColumn::make('version.title')->label(__('curriculum.fields.title')),
                TextColumn::make('credits')->label(__('curriculum.fields.credits')),
                TextColumn::make('status')->label(__('registration.fields.status'))->formatStateUsing(fn (EnrollmentStatus $state) => $state->label())->badge()
                    ->color(fn (EnrollmentStatus $state) => match ($state) {
                        EnrollmentStatus::Enrolled => 'success',
                        EnrollmentStatus::Completed => 'info',
                        default => 'gray',
                    }),
                IconColumn::make('is_retake')->label(__('registration.fields.is_retake'))->boolean(),
                TextColumn::make('source')->label(__('registration.fields.source'))->formatStateUsing(fn (EnrollmentSource $state) => $state->label())->toggleable(),
                TextColumn::make('enrolled_at')->label(__('registration.fields.enrolled_at'))->dateTime()->sortable(),
            ])
            ->defaultSort('enrolled_at', 'desc')
            ->filters([
                SelectFilter::make('term_id')->label(__('reference.resources.terms.label'))
                    ->options(fn () => Term::query()->orderByDesc('start_date')->get()->mapWithKeys(fn (Term $term) => [$term->id => $term->name]))
                    ->default(fn () => SectionResource::defaultTermId()),
                SelectFilter::make('status')->label(__('registration.fields.status'))
                    ->options(collect(EnrollmentStatus::cases())->mapWithKeys(fn (EnrollmentStatus $status) => [$status->value => $status->label()]))
                    ->default(EnrollmentStatus::Enrolled->value),
            ])
            ->headerActions([self::staffEnrollAction()])
            ->recordActions([
                self::endAction('drop'),
                self::endAction('withdraw'),
            ]);
    }

    /**
     * Staff registration: same rules, chosen ones waived with a reason. With a section
     * (the section roster), only the student is chosen.
     */
    public static function staffEnrollAction(?Section $section = null): Action
    {
        return Action::make('staffEnroll')->label(__('registration.actions.staff_enroll'))->icon(Heroicon::OutlinedPlusCircle)
            ->authorize(fn () => Gate::allows('create', Enrollment::class))
            ->schema([
                StudentRecordSelect::make(),
                Select::make('section_id')->label(__('offering.fields.section'))->required($section === null)->searchable()->hidden($section !== null)
                    ->getSearchResultsUsing(fn (string $search) => Section::query()->published()->where('term_id', SectionResource::defaultTermId())
                        ->whereHas('course', fn (Builder $course) => $course->where('code', 'ilike', $search.'%'))->with('course')->limit(20)->get()
                        ->mapWithKeys(fn (Section $section) => [$section->id => $section->label()]))
                    ->getOptionLabelUsing(fn ($value) => Section::query()->with('course')->find($value)?->label()),
                CheckboxList::make('waive')->label(__('registration.fields.waive'))->columns(2)
                    ->options(collect(RuleCode::cases())->reject(fn (RuleCode $code) => in_array($code, [RuleCode::SectionOpen, RuleCode::Capacity], true))
                        ->mapWithKeys(fn (RuleCode $code) => [$code->value => $code->label()])),
                Toggle::make('waitlist')->label(__('registration.fields.waitlist_if_full')),
                Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000),
            ])
            ->action(function (array $data) use ($section) {
                $outcome = app(EnrollStudent::class)->handle(
                    StudentRecord::query()->findOrFail($data['student_record_id']),
                    $section ?? Section::query()->findOrFail($data['section_id']),
                    self::actor(),
                    new EnrollOptions(
                        waitlistIfFull: (bool) ($data['waitlist'] ?? false),
                        waive: array_map(fn (string $code) => RuleCode::from($code), $data['waive'] ?? []),
                        reason: $data['reason'],
                    ),
                );

                $notification = Notification::make()->title($outcome->outcome->label())->body(implode("\n", $outcome->messages()));
                ($outcome->succeeded() ? $notification->success() : $notification->warning())->send();
            });
    }

    public static function endAction(string $kind): Action
    {
        return Action::make($kind)->label(__('registration.actions.'.$kind))->color('danger')
            ->icon($kind === 'drop' ? Heroicon::OutlinedMinusCircle : Heroicon::OutlinedArrowRightStartOnRectangle)
            ->visible(fn (Enrollment $record) => $record->status === EnrollmentStatus::Enrolled && Gate::allows('update', $record))
            ->requiresConfirmation()
            ->schema([Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000)])
            ->action(fn (Enrollment $record, array $data) => ShowsActionErrors::run(fn () => $kind === 'drop'
                ? app(DropEnrollment::class)->drop($record, self::actor(), $data['reason'])
                : app(DropEnrollment::class)->withdraw($record, self::actor(), $data['reason'])));
    }

    public static function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    public static function getPages(): array
    {
        return ['index' => ListEnrollments::route('/')];
    }
}
