<?php

namespace App\Filament\Admin\Resources\Registration\Overrides;

use App\Domain\Registration\Actions\ManageHolds;
use App\Domain\Registration\Enums\RuleCode;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Curriculum\Courses\CourseResource;
use App\Filament\Admin\Resources\Offering\Sections\SectionResource;
use App\Filament\Admin\Resources\Registration\Overrides\Pages\ListRegistrationOverrides;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Filament\Admin\Support\StudentRecordSelect;
use App\Models\Curriculum\Course;
use App\Models\Offering\Section;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\Registration\RegistrationOverride;
use App\Models\Term\Term;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Rule overrides (legacy SRALLOW): one student may pass one rule in one term, optionally
 * only for a section or course, until revoked or expired.
 */
class RegistrationOverrideResource extends Resource
{
    protected static ?string $model = RegistrationOverride::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Registration;

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'registration/overrides';

    public static function getModelLabel(): string
    {
        return __('registration.resources.overrides.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('registration.resources.overrides.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('studentRecord', fn (Builder $record) => StudentRecord::constrainVisibleTo($record, self::actor()));
    }

    /**
     * Rules an override can pass: everything except the section state and seats.
     *
     * @return array<string, string>
     */
    public static function overridableRules(): array
    {
        return collect(RuleCode::cases())->reject(fn (RuleCode $code) => $code->isCore() && $code !== RuleCode::Window)
            ->mapWithKeys(fn (RuleCode $code) => [$code->value => $code->label()])->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['studentRecord.person', 'term', 'section.course', 'course']))
            ->columns([
                TextColumn::make('studentRecord.student_number')->label(__('people.fields.student_number'))->searchable(),
                TextColumn::make('name')->label(__('people.fields.name'))->state(fn (RegistrationOverride $record) => $record->studentRecord->person->displayName())
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('studentRecord.person', fn (Builder $person) => Person::constrainToSearch($person, $search))),
                TextColumn::make('term.name')->label(__('reference.resources.terms.label')),
                TextColumn::make('rule_code')->label(__('registration.fields.rule'))->formatStateUsing(fn (RuleCode $state) => $state->label())->badge(),
                TextColumn::make('limit')->label(__('registration.fields.override_limit'))
                    ->state(fn (RegistrationOverride $record) => $record->section?->label() ?? $record->course->code ?? __('registration.fields.whole_term')),
                TextColumn::make('reason')->label(__('common.reason'))->limit(40)->tooltip(fn (RegistrationOverride $record) => $record->reason),
                TextColumn::make('expires_at')->label(__('registration.fields.expires_at'))->dateTime()->placeholder('-'),
                TextColumn::make('revoked_at')->label(__('registration.fields.revoked_at'))->dateTime()->placeholder('-'),
                TextColumn::make('created_at')->label(__('registration.fields.granted_at'))->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('term_id')->label(__('reference.resources.terms.label'))
                    ->options(fn () => Term::query()->orderByDesc('start_date')->get()->mapWithKeys(fn (Term $term) => [$term->id => $term->name])),
                TernaryFilter::make('in_force')->label(__('registration.fields.in_force'))->default(true)
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('revoked_at')->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now())),
                        false: fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNotNull('revoked_at')->orWhere('expires_at', '<=', now())),
                    ),
            ])
            ->recordActions([
                Action::make('revoke')->label(__('registration.actions.revoke_override'))->color('danger')->icon(Heroicon::OutlinedXCircle)
                    ->visible(fn (RegistrationOverride $record) => $record->revoked_at === null && Gate::allows('update', $record))
                    ->requiresConfirmation()
                    ->action(fn (RegistrationOverride $record) => ShowsActionErrors::run(fn () => app(ManageHolds::class)->revokeOverride($record, self::actor()))),
            ]);
    }

    public static function grantAction(): Action
    {
        return Action::make('grantOverride')->label(__('registration.actions.grant_override'))->icon(Heroicon::OutlinedShieldCheck)
            ->authorize(fn () => Gate::allows('create', RegistrationOverride::class))
            ->schema([
                StudentRecordSelect::make(),
                Select::make('term_id')->label(__('reference.resources.terms.label'))->required()->live()
                    ->options(fn () => Term::query()->whereIn('status', ['planned', 'published'])->orderByDesc('start_date')->get()->mapWithKeys(fn (Term $term) => [$term->id => $term->name]))
                    ->default(fn () => SectionResource::defaultTermId()),
                Select::make('rule_code')->label(__('registration.fields.rule'))->required()->options(fn () => self::overridableRules()),
                Select::make('section_id')->label(__('offering.fields.section'))->searchable()->helperText(__('registration.help.override_section'))
                    ->getSearchResultsUsing(fn (string $search, Get $get) => Section::query()->where('term_id', $get('term_id'))
                        ->whereHas('course', fn (Builder $course) => $course->where('code', 'ilike', $search.'%'))->with('course')->limit(20)->get()
                        ->mapWithKeys(fn (Section $section) => [$section->id => $section->label()]))
                    ->getOptionLabelUsing(fn ($value) => Section::query()->with('course')->find($value)?->label()),
                Select::make('course_id')->label(__('curriculum.resources.courses.label'))->searchable()->helperText(__('registration.help.override_course'))
                    ->getSearchResultsUsing(fn (string $search) => CourseResource::courseSearch($search))
                    ->getOptionLabelUsing(fn ($value) => Course::query()->find($value)?->code),
                DateTimePicker::make('expires_at')->label(__('registration.fields.expires_at'))->seconds(false),
                Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000),
            ])
            ->action(fn (array $data) => ShowsActionErrors::run(fn () => app(ManageHolds::class)->grantOverride(
                StudentRecord::query()->findOrFail($data['student_record_id']),
                Term::query()->findOrFail($data['term_id']),
                RuleCode::from($data['rule_code']),
                $data['reason'],
                self::actor(),
                [
                    'section_id' => $data['section_id'] ?? null,
                    'course_id' => $data['course_id'] ?? null,
                    'expires_at' => filled($data['expires_at'] ?? null) ? CarbonImmutable::parse($data['expires_at']) : null,
                ],
            )));
    }

    public static function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    public static function getPages(): array
    {
        return ['index' => ListRegistrationOverrides::route('/')];
    }
}
