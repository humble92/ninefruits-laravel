<?php

namespace App\Filament\Admin\Resources\Admissions\Applications;

use App\Domain\Admission\Actions\MatriculateApplicant;
use App\Domain\Admission\Actions\ReleaseDecisions;
use App\Domain\Admission\Enums\ApplicationStatus;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Admissions\Applications\Pages\ListApplications;
use App\Filament\Admin\Resources\Admissions\Applications\Pages\ViewApplication;
use App\Filament\Admin\Resources\People\PersonResource;
use App\Models\Admission\AdmissionCycle;
use App\Models\Admission\Application;
use App\Models\Institution\LookupValue;
use App\Models\Person\Person;
use App\Models\Term\Term;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use UnitEnum;

/**
 * Applications for staff: review, decide, release decisions and matriculate. Applicants
 * create and submit applications in the portal; staff never edit their answers.
 */
class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Admissions;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'admissions/applications';

    protected static ?string $recordTitleAttribute = 'application_number';

    public static function getModelLabel(): string
    {
        return __('admissions.resources.applications.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admissions.resources.applications.plural_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = self::getEloquentQuery()->whereIn('status', [ApplicationStatus::Submitted, ApplicationStatus::UnderReview])->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * Drafts are the applicant's private work and are not listed.
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var User $user */
        $user = Auth::user();
        $query = parent::getEloquentQuery()->where('status', '!=', ApplicationStatus::Draft);
        Application::constrainVisibleTo($query, $user);

        return $query;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Section::make(__('admissions.sections.application'))->columns(2)->schema([
                TextEntry::make('application_number')->label(__('admissions.fields.application_number')),
                TextEntry::make('status')->label(__('admissions.fields.status'))->formatStateUsing(fn (ApplicationStatus $state) => $state->label())->badge(),
                TextEntry::make('cycle.name')->label(__('admissions.fields.cycle')),
                TextEntry::make('program.name')->label(__('reference.fields.programs')),
                TextEntry::make('admissionType.name')->label(__('people.fields.admission_type'))->placeholder('-'),
                TextEntry::make('submitted_at')->label(__('admissions.fields.submitted_at'))->dateTime(),
                TextEntry::make('previous_school_name')->label(__('admissions.fields.previous_school_name'))->placeholder('-'),
                TextEntry::make('previousSchoolCountry.name')->label(__('admissions.fields.previous_school_country'))->placeholder('-'),
                TextEntry::make('previous_graduation_date')->label(__('admissions.fields.previous_graduation_date'))->date()->placeholder('-'),
                TextEntry::make('personal_statement')->label(__('admissions.fields.personal_statement'))->columnSpanFull()->prose(),
            ]),
            Section::make(__('admissions.sections.applicant'))->columns(2)->schema([
                TextEntry::make('person_name')->label(__('people.fields.name'))
                    ->state(fn (Application $record) => $record->person->displayName().' ('.$record->person->romanizedName().')')
                    ->url(fn (Application $record) => PersonResource::canView($record->person) ? PersonResource::getUrl('view', ['record' => $record->person]) : null),
                TextEntry::make('person.birth_date')->label(__('people.fields.birth_date'))->date()->placeholder('-'),
                TextEntry::make('person.email')->label(__('people.fields.contact_email'))->placeholder('-'),
                TextEntry::make('person.phone')->label(__('people.fields.phone'))->placeholder('-'),
                TextEntry::make('earlier_records')->label(__('admissions.fields.earlier_records'))
                    ->state(fn (Application $record) => $record->person->studentRecords()->pluck('student_number')->implode(', ') ?: __('common.none')),
                TextEntry::make('documents')->label(__('admissions.sections.documents'))->columnSpanFull()
                    ->state(fn (Application $record) => self::documentLinks($record))
                    ->placeholder(__('common.none')),
            ]),
            Section::make(__('admissions.sections.decision'))->columns(2)->columnSpanFull()->schema([
                TextEntry::make('decidedBy.email')->label(__('admissions.fields.decided_by'))->placeholder('-'),
                TextEntry::make('decided_at')->label(__('admissions.fields.decided_at'))->dateTime()->placeholder('-'),
                TextEntry::make('decision_note')->label(__('admissions.fields.decision_note'))->placeholder('-'),
                IconEntry::make('decision_released_at')->label(__('admissions.fields.decision_released'))->boolean()
                    ->state(fn (Application $record) => $record->decision_released_at !== null),
                TextEntry::make('offer_expires_at')->label(__('admissions.fields.offer_expires_at'))->dateTime()->placeholder('-'),
                TextEntry::make('studentRecord.student_number')->label(__('people.fields.student_number'))->placeholder('-'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['person', 'program', 'cycle']))
            ->columns([
                TextColumn::make('application_number')->label(__('admissions.fields.application_number'))->searchable()->sortable(),
                TextColumn::make('person_name')->label(__('people.fields.name'))->state(fn (Application $record) => $record->person->displayName())
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('person', fn (Builder $person) => Person::constrainToSearch($person, $search))),
                TextColumn::make('program.name')->label(__('reference.fields.programs')),
                TextColumn::make('cycle.name')->label(__('admissions.fields.cycle'))->toggleable(),
                TextColumn::make('status')->label(__('admissions.fields.status'))->formatStateUsing(fn (ApplicationStatus $state) => $state->label())->badge()
                    ->color(fn (ApplicationStatus $state) => match ($state) {
                        ApplicationStatus::Submitted, ApplicationStatus::UnderReview => 'warning',
                        ApplicationStatus::Admitted, ApplicationStatus::Confirmed => 'success',
                        ApplicationStatus::Rejected => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('released')->label(__('admissions.fields.decision_released'))->boolean()
                    ->state(fn (Application $record) => $record->decision_released_at !== null),
                TextColumn::make('submitted_at')->label(__('admissions.fields.submitted_at'))->dateTime()->sortable(),
            ])
            ->defaultSort('submitted_at')
            ->filters([
                SelectFilter::make('admission_cycle_id')->label(__('admissions.fields.cycle'))
                    ->options(fn () => AdmissionCycle::query()->orderByDesc('opens_at')->get()->pluck('name', 'id')),
                SelectFilter::make('status')->label(__('admissions.fields.status'))->multiple()
                    ->options(collect(ApplicationStatus::cases())->reject(fn (ApplicationStatus $status) => $status === ApplicationStatus::Draft)
                        ->mapWithKeys(fn (ApplicationStatus $status) => [$status->value => $status->label()])),
            ])
            ->recordActions([ViewAction::make()])
            ->toolbarActions([
                BulkAction::make('releaseDecisions')->label(__('admissions.actions.release_decisions'))->icon(Heroicon::OutlinedMegaphone)
                    ->requiresConfirmation()->modalDescription(__('admissions.help.release_decisions'))
                    ->action(function (Collection $records, ReleaseDecisions $release) {
                        $count = $release->handle($records->whereInstanceOf(Application::class)->load(['cycle', 'person.user', 'program']), self::actor());
                        Notification::make()->success()->title(trans_choice('admissions.notifications.released', $count))->send();
                    }),
                BulkAction::make('matriculate')->label(__('admissions.actions.matriculate'))->icon(Heroicon::OutlinedAcademicCap)
                    ->schema([self::admissionTermField()])
                    ->action(function (Collection $records, array $data, MatriculateApplicant $matriculate) {
                        $term = Term::query()->findOrFail($data['admission_term_id']);
                        $done = 0;
                        $skipped = 0;

                        foreach ($records->whereInstanceOf(Application::class) as $record) {
                            try {
                                $matriculate->handle($record, $term, self::actor());
                                $done++;
                            } catch (ValidationException|AuthorizationException) {
                                $skipped++;
                            }
                        }

                        Notification::make()->success()->title(__('admissions.notifications.matriculated_bulk', ['count' => $done, 'skipped' => $skipped]))->send();
                    }),
            ]);
    }

    /**
     * Download links of the uploaded documents, labelled with their document type.
     */
    public static function documentLinks(Application $record): ?HtmlString
    {
        $types = LookupValue::query()->whereIn('id', $record->getMedia(Application::DOCUMENTS_COLLECTION)->map(fn (Media $media) => (int) $media->getCustomProperty('document_type_id')))->get()->keyBy('id');
        $links = $record->getMedia(Application::DOCUMENTS_COLLECTION)->map(fn (Media $media) => sprintf(
            '%s: <a href="%s" class="underline">%s</a>',
            e($types->get((int) $media->getCustomProperty('document_type_id'))->name ?? '-'),
            e(route('applications.documents.show', [$record, $media])),
            e($media->file_name),
        ));

        return $links->isEmpty() ? null : new HtmlString($links->implode('<br>'));
    }

    public static function admissionTermField(): Select
    {
        return Select::make('admission_term_id')->label(__('admissions.fields.admission_term'))
            ->options(fn () => Term::query()->whereIn('status', ['planned', 'published'])->orderBy('start_date')->get()
                ->mapWithKeys(fn (Term $term) => [$term->id => $term->name.' ('.$term->start_date->toDateString().')']))
            ->helperText(__('admissions.help.admission_term'))
            ->required();
    }

    public static function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApplications::route('/'),
            'view' => ViewApplication::route('/{record}'),
        ];
    }
}
