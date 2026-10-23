<?php

namespace App\Filament\Admin\Resources\Offering\Sections;

use App\Domain\Grading\Enums\EvaluationMethod;
use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Offering\Services\Timetable;
use App\Domain\Term\Services\TermResolver;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Offering\Sections\Pages\CreateSection;
use App\Filament\Admin\Resources\Offering\Sections\Pages\EditSection;
use App\Filament\Admin\Resources\Offering\Sections\Pages\ListSections;
use App\Filament\Admin\Resources\Offering\Sections\RelationManagers\ClassMeetingsRelationManager;
use App\Filament\Admin\Resources\Offering\Sections\RelationManagers\InstructorsRelationManager;
use App\Filament\Admin\Resources\Offering\Sections\RelationManagers\MeetingsRelationManager;
use App\Filament\Admin\Resources\Offering\Sections\RelationManagers\RosterRelationManager;
use App\Filament\Admin\Resources\Offering\Sections\RelationManagers\SeatGroupsRelationManager;
use App\Filament\Admin\Resources\Offering\Sections\RelationManagers\WaitlistRelationManager;
use App\Filament\Admin\Resources\Reference\OrgUnits\OrgUnitResource;
use App\Filament\Admin\Support\TargetCriteriaFields;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Offering\Section;
use App\Models\Person\Instructor;
use App\Models\Term\Term;
use App\Models\Term\TermSession;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Sections (legacy SFOPOBJECT): staff of the offering unit plan them per term session,
 * with instructors, meetings and rooms, eligibility and seat groups.
 */
class SectionResource extends Resource
{
    protected static ?string $model = Section::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Curriculum;

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'offering/sections';

    public static function getModelLabel(): string
    {
        return __('offering.resources.sections.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('offering.resources.sections.plural_label');
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User $user */
        $user = Auth::user();
        $query = parent::getEloquentQuery();
        Section::constrainManageableBy($query, $user);

        return $query;
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof Section ? $record->label().' '.$record->version->title : '';
    }

    /**
     * Sessions grouped by term, for selects.
     *
     * @return array<string, array<int, string>>
     */
    public static function sessionOptions(): array
    {
        return Term::query()->whereIn('status', ['planned', 'published'])->orderByDesc('start_date')->with('sessions')->get()
            ->mapWithKeys(fn (Term $term) => [$term->name.' ('.$term->code.')' => $term->sessions->mapWithKeys(fn (TermSession $session) => [
                $session->id => $session->name.' '.$session->start_date->format('m-d').'~'.$session->end_date->format('m-d'),
            ])->all()])
            ->all();
    }

    /**
     * The term staff most likely work on: the one in session, else the next one.
     */
    public static function defaultTermId(): ?int
    {
        return app(TermResolver::class)->inSession()->first()->id
            ?? Term::query()->where('start_date', '>=', now()->toDateString())->orderBy('start_date')->value('id');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            FormSection::make()->columns(3)->schema([
                Select::make('term_session_id')->label(__('offering.fields.session'))->options(fn (?Section $record) => TermSession::query()
                    ->where('term_id', $record?->term_id)->get()->pluck('name', 'id'))->required()
                    ->helperText(__('offering.help.change_session')),
                TextInput::make('section_code')->label(__('offering.fields.section_code'))->required()->maxLength(16)->alphaDash(),
                TextInput::make('capacity')->label(__('offering.fields.capacity'))->integer()->minValue(0)->maxValue(2000)->required(),
                Select::make('evaluation_method')->label(__('grading.fields.evaluation_method'))->required()->default(EvaluationMethod::Relative->value)
                    ->options(collect(EvaluationMethod::cases())->mapWithKeys(fn (EvaluationMethod $method) => [$method->value => $method->label()]))
                    ->helperText(__('grading.help.evaluation_method')),
                TextInput::make('waitlist_capacity')->label(__('registration.fields.waitlist_capacity'))->integer()->minValue(0)->maxValue(500)->default(0)
                    ->helperText(__('registration.help.waitlist_capacity')),
                Select::make('org_unit_id')->label(__('offering.fields.offering_unit'))->options(fn () => OrgUnitResource::unitOptions())->searchable()->required(),
                TextInput::make('instruction_language')->label(__('offering.fields.instruction_language'))->maxLength(16)->helperText(__('offering.help.instruction_language')),
                TranslatableFields::make('note', __('offering.fields.note'), required: false)->columnSpanFull(),
            ]),
            TargetCriteriaFields::make('eligibility', __('offering.fields.eligibility'), __('offering.help.eligibility')),
            TargetCriteriaFields::make('exclusion', __('offering.fields.exclusion'), __('offering.help.exclusion')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['course', 'version', 'session', 'instructors.person', 'meetings.room.building']))
            ->columns([
                TextColumn::make('label')->label(__('offering.fields.section'))->state(fn (Section $record) => $record->label())
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('course', fn (Builder $course) => $course->where('code', 'ilike', $search.'%'))),
                TextColumn::make('version.title')->label(__('curriculum.fields.title'))
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('version', fn (Builder $version) => $version->whereRaw('util.i18n_search_text(title) ILIKE ?', ['%'.$search.'%']))),
                TextColumn::make('version.credits')->label(__('curriculum.fields.credits')),
                TextColumn::make('session.code')->label(__('offering.fields.session'))->badge()->color('gray'),
                TextColumn::make('instructors')->label(__('offering.fields.instructors'))
                    ->state(fn (Section $record) => $record->instructors->map(fn (Instructor $instructor) => $instructor->person->displayName())->implode(', ') ?: '-'),
                TextColumn::make('schedule')->label(__('offering.fields.schedule'))->state(fn (Section $record) => Timetable::summary($record->meetings) ?: '-')->wrap(),
                TextColumn::make('capacity')->label(__('offering.fields.capacity'))->state(fn (Section $record) => $record->enrolled_count.' / '.$record->capacity),
                TextColumn::make('status')->label(__('offering.fields.status'))->formatStateUsing(fn (SectionStatus $state) => $state->label())->badge()
                    ->color(fn (SectionStatus $state) => match ($state) {
                        SectionStatus::Draft => 'gray',
                        SectionStatus::Published => 'success',
                        SectionStatus::Cancelled => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('term_id')->label(__('reference.resources.terms.label'))
                    ->options(fn () => Term::query()->orderByDesc('start_date')->get()->mapWithKeys(fn (Term $term) => [$term->id => $term->name.' ('.$term->code.')']))
                    ->default(fn () => self::defaultTermId()),
                SelectFilter::make('org_unit_id')->label(__('offering.fields.offering_unit'))->options(fn () => OrgUnitResource::unitOptions()),
                SelectFilter::make('status')->label(__('offering.fields.status'))
                    ->options(collect(SectionStatus::cases())->mapWithKeys(fn (SectionStatus $status) => [$status->value => $status->label()])),
            ])
            ->defaultSort('course_id')
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [MeetingsRelationManager::class, InstructorsRelationManager::class, SeatGroupsRelationManager::class, RosterRelationManager::class, WaitlistRelationManager::class, ClassMeetingsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSections::route('/'),
            'create' => CreateSection::route('/create'),
            'edit' => EditSection::route('/{record}/edit'),
        ];
    }
}
