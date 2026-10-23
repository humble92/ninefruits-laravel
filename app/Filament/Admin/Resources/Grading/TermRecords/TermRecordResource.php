<?php

namespace App\Filament\Admin\Resources\Grading\TermRecords;

use App\Domain\Grading\Enums\AcademicStanding;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Grading\TermRecords\Pages\ListTermRecords;
use App\Filament\Admin\Resources\Offering\Sections\SectionResource;
use App\Models\Grading\TermRecord;
use App\Models\Organization\Program;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\Term\Term;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Term records: credits, GPA, academic standing and honors per student and term (legacy
 * report_warn*, report_toplevel* reports). Term close and recalculation start here.
 */
class TermRecordResource extends Resource
{
    protected static ?string $model = TermRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Grading;

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'grading/term-records';

    public static function getModelLabel(): string
    {
        return __('grading.resources.term_records.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('grading.resources.term_records.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User $user */
        $user = Auth::user();

        return parent::getEloquentQuery()->whereHas('studentRecord', fn (Builder $record) => StudentRecord::constrainVisibleTo($record, $user));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['studentRecord.person', 'studentRecord.program', 'term']))
            ->columns([
                TextColumn::make('studentRecord.student_number')->label(__('people.fields.student_number'))->searchable(),
                TextColumn::make('name')->label(__('people.fields.name'))->state(fn (TermRecord $record) => $record->studentRecord->person->displayName())
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('studentRecord.person', fn (Builder $person) => Person::constrainToSearch($person, $search))),
                TextColumn::make('studentRecord.program.name')->label(__('reference.resources.programs.label'))->toggleable(),
                TextColumn::make('term.name')->label(__('reference.resources.terms.label')),
                TextColumn::make('term_gpa')->label(__('grading.fields.term_gpa'))->sortable()->placeholder('-'),
                TextColumn::make('earned_credits')->label(__('grading.fields.earned_credits'))->formatStateUsing(fn (string $state, TermRecord $record) => (float) $state.' / '.(float) $record->attempted_credits),
                TextColumn::make('cumulative_gpa')->label(__('grading.fields.cumulative_gpa'))->sortable()->placeholder('-'),
                TextColumn::make('academic_standing')->label(__('grading.fields.standing'))->formatStateUsing(fn (?AcademicStanding $state) => $state?->label())->badge()
                    ->color(fn (?AcademicStanding $state) => match ($state) {
                        AcademicStanding::Warning => 'warning',
                        AcademicStanding::DismissalReview => 'danger',
                        default => 'gray',
                    })->placeholder('-'),
                TextColumn::make('warning_count')->label(__('grading.fields.warning_count'))->toggleable(),
                TextColumn::make('honors_rank')->label(__('grading.fields.honors_rank'))->placeholder('-')->sortable(),
                IconColumn::make('closed_at')->label(__('grading.fields.closed'))->boolean()->state(fn (TermRecord $record) => $record->closed_at !== null),
            ])
            ->defaultSort('term_gpa', 'desc')
            ->filters([
                SelectFilter::make('term_id')->label(__('reference.resources.terms.label'))
                    ->options(fn () => Term::query()->orderByDesc('start_date')->get()->mapWithKeys(fn (Term $term) => [$term->id => $term->name.' ('.$term->code.')']))
                    ->default(fn () => TermRecord::query()->join('terms', 'terms.id', '=', 'term_records.term_id')->orderByDesc('terms.start_date')->value('term_records.term_id') ?? SectionResource::defaultTermId()),
                SelectFilter::make('academic_standing')->label(__('grading.fields.standing'))
                    ->options(collect(AcademicStanding::cases())->mapWithKeys(fn (AcademicStanding $standing) => [$standing->value => $standing->label()])),
                TernaryFilter::make('is_honors')->label(__('grading.fields.honors')),
                SelectFilter::make('program')->label(__('reference.resources.programs.label'))
                    ->options(fn () => Program::query()->orderBy('code')->get()->mapWithKeys(fn (Program $program) => [$program->id => $program->code.' '.$program->name]))
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn (Builder $query, $id) => $query->whereHas('studentRecord', fn (Builder $record) => $record->where('program_id', $id)))),
            ])
            ->recordActions([
                Action::make('transcript')->label(__('grading.transcript.title'))->icon(Heroicon::OutlinedDocumentText)->color('gray')
                    ->url(fn (TermRecord $record) => route('transcripts.show', $record->studentRecord))->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListTermRecords::route('/')];
    }
}
