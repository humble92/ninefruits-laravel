<?php

namespace App\Filament\Admin\Resources\Graduation\Audits;

use App\Domain\Curriculum\Enums\ProgramRole;
use App\Domain\Graduation\Actions\GraduateStudents;
use App\Domain\Graduation\Actions\RunDegreeAudits;
use App\Domain\Person\Enums\StudentStatus;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Graduation\Audits\Pages\ListDegreeAudits;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Graduation\DegreeAudit;
use App\Models\Organization\Program;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\Term\Term;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * The latest stored audit per student and program: the graduation candidate list
 * (legacy report_pregrade with its hard-coded 154/160 credits). Run audits, open one,
 * graduate the satisfied ones.
 */
class DegreeAuditResource extends Resource
{
    protected static ?string $model = DegreeAudit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Graduation;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'graduation/audits';

    public static function getModelLabel(): string
    {
        return __('graduation.resources.audits.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('graduation.resources.audits.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('id', DegreeAudit::query()->selectRaw('max(id)')->groupBy('student_record_id', 'program_role'))->whereHas('studentRecord', fn (Builder $record) => StudentRecord::constrainVisibleTo($record, self::actor()));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['studentRecord.person', 'program', 'term']))
            ->columns([
                TextColumn::make('studentRecord.student_number')->label(__('people.fields.student_number'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('people.fields.name'))->state(fn (DegreeAudit $record) => $record->studentRecord->person->displayName())
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('studentRecord.person', fn (Builder $person) => Person::constrainToSearch($person, $search))),
                TextColumn::make('program.name')->label(__('reference.resources.programs.label'))->description(fn (DegreeAudit $record) => $record->program_role->label()),
                TextColumn::make('studentRecord.year_level')->label(__('people.fields.year_level')),
                TextColumn::make('studentRecord.status')->label(__('registration.fields.status'))->formatStateUsing(fn ($state) => $state->label()),
                IconColumn::make('is_satisfied')->label(__('graduation.fields.is_satisfied'))->boolean(),
                TextColumn::make('earned_credits')->label(__('graduation.fields.earned_credits'))->formatStateUsing(fn (string $state, DegreeAudit $record) => $state.($record->required_credits !== null ? ' / '.$record->required_credits : '')),
                TextColumn::make('cumulative_gpa')->label(__('grading.fields.cumulative_gpa'))->placeholder('-'),
                TextColumn::make('audited_at')->label(__('graduation.fields.audited_at'))->dateTime()->sortable(),
            ])
            ->defaultSort('audited_at', 'desc')
            ->filters([
                SelectFilter::make('program_role')->label(__('curriculum.fields.program_role'))->default(ProgramRole::Primary->value)
                    ->options(collect(ProgramRole::cases())->mapWithKeys(fn (ProgramRole $role) => [$role->value => $role->label()])),
                TernaryFilter::make('is_satisfied')->label(__('graduation.fields.is_satisfied')),
                SelectFilter::make('program_id')->label(__('reference.resources.programs.label'))
                    ->options(fn () => Program::query()->orderBy('code')->get()->mapWithKeys(fn (Program $program) => [$program->id => $program->code.' '.$program->name])),
                SelectFilter::make('status')->label(__('registration.fields.status'))->default('enrolled')
                    ->query(fn (Builder $query, array $data) => $query->when(filled($data['value'] ?? null), fn (Builder $q) => $q->whereHas('studentRecord', fn (Builder $r) => $r->where('status', $data['value']))))
                    ->options([StudentStatus::Enrolled->value => StudentStatus::Enrolled->label(), StudentStatus::Graduated->value => StudentStatus::Graduated->label()]),
            ])
            ->headerActions([
                Action::make('run')->label(__('graduation.actions.run_audits'))->icon(Heroicon::OutlinedPlay)
                    ->visible(fn () => self::actor()->can('graduation.audit'))
                    ->schema([
                        Select::make('term_id')->label(__('reference.resources.terms.label'))
                            ->options(fn () => Term::query()->orderByDesc('start_date')->get()->mapWithKeys(fn (Term $term) => [$term->id => $term->name.' ('.$term->code.')'])),
                        Select::make('min_year_level')->label(__('graduation.fields.min_year_level'))->options(array_combine(range(1, 6), array_map('strval', range(1, 6))))->default(4),
                    ])
                    ->action(function (array $data, RunDegreeAudits $audits) {
                        $summary = $audits->forAll(filled($data['term_id'] ?? null) ? Term::query()->find($data['term_id']) : null, self::actor(), filled($data['min_year_level'] ?? null) ? (int) $data['min_year_level'] : null);
                        Notification::make()->success()->title(__('graduation.notifications.audits_run', $summary))->send();
                    }),
            ])
            ->recordActions([
                Action::make('open')->label(__('graduation.actions.open'))->icon(Heroicon::OutlinedMagnifyingGlass)->color('gray')
                    ->modalHeading(fn (DegreeAudit $record) => $record->studentRecord->student_number.' '.$record->studentRecord->person->displayName())
                    ->modalContent(fn (DegreeAudit $record) => view('filament.admin.degree-audit', ['result' => $record->result, 'auditedAt' => $record->audited_at]))
                    ->modalSubmitAction(false)->modalCancelActionLabel(__('evaluation.portal.back')),
                Action::make('rerun')->label(__('graduation.actions.rerun'))->icon(Heroicon::OutlinedArrowPath)->color('gray')
                    ->visible(fn () => self::actor()->can('graduation.audit'))
                    ->action(fn (DegreeAudit $record, RunDegreeAudits $audits) => ShowsActionErrors::run(fn () => $audits->forRecord($record->studentRecord, self::actor(), $record->term))),
            ])
            ->toolbarActions([
                BulkAction::make('graduate')->label(__('graduation.actions.graduate'))->icon(Heroicon::OutlinedAcademicCap)->color('success')
                    ->visible(fn () => self::actor()->can('graduation.process'))
                    ->schema([
                        DatePicker::make('graduation_date')->label(__('people.fields.graduation_date'))->required()->default(now()),
                        Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000),
                    ])
                    ->action(function (Collection $records, array $data, GraduateStudents $graduate) {
                        /** @var Collection<int, DegreeAudit> $records */
                        $students = $records->map(fn (DegreeAudit $audit): StudentRecord => $audit->studentRecord)->all();
                        $summary = $graduate->batch($students, CarbonImmutable::parse((string) $data['graduation_date']), self::actor(), (string) $data['reason']);
                        $notification = Notification::make()->title(__('graduation.notifications.graduated', ['count' => $summary['graduated'], 'errors' => count($summary['errors'])]))->body(implode("\n", array_slice($summary['errors'], 0, 10)));
                        $summary['errors'] === [] ? $notification->success() : $notification->warning()->persistent();
                        $notification->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    public static function getPages(): array
    {
        return ['index' => ListDegreeAudits::route('/')];
    }
}
