<?php

namespace App\Filament\Admin\Resources\Evaluation\Sections;

use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Evaluation\Sections\Pages\ListSectionEvaluations;
use App\Filament\Admin\Resources\Offering\Sections\SectionResource;
use App\Filament\Admin\Resources\Reference\OrgUnits\OrgUnitResource;
use App\Models\Evaluation\TermEvaluation;
use App\Models\Offering\Section;
use App\Models\Person\Instructor;
use App\Models\Term\Term;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Response rates per section for staff (legacy counsel/academy_list.php); the results open
 * the section's results page.
 */
class SectionEvaluationResource extends Resource
{
    protected static ?string $model = Section::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Evaluation;

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'evaluation/sections';

    public static function getModelLabel(): string
    {
        return __('evaluation.resources.sections.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('evaluation.resources.sections.plural_label');
    }

    public static function canViewAny(): bool
    {
        return self::actor()->can(Permission::EvaluationsView->value) || self::actor()->can(Permission::EvaluationsManage->value);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->whereIn('term_id', TermEvaluation::query()->select('term_id'));
        Section::constrainToScope($query, self::actor(), Permission::EvaluationsView, Permission::EvaluationsManage);

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['course', 'version', 'instructors.person'])
                ->withCount([
                    'enrollments as expected_count' => fn (Builder $enrollment) => $enrollment->whereIn('status', [EnrollmentStatus::Enrolled, EnrollmentStatus::Completed])
                        ->whereHas('version', fn (Builder $version) => $version->where('requires_evaluation', true)),
                ])
                ->addSelect(['responses_count' => fn ($sub) => $sub->from('evaluation_responses')->selectRaw('count(*)')->whereColumn('evaluation_responses.section_id', 'sections.id')]))
            ->columns([
                TextColumn::make('label')->label(__('offering.fields.section'))->state(fn (Section $record) => $record->label())
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('course', fn (Builder $course) => $course->where('code', 'ilike', $search.'%'))),
                TextColumn::make('version.title')->label(__('curriculum.fields.title')),
                TextColumn::make('instructors')->label(__('offering.fields.instructors'))
                    ->state(fn (Section $record) => $record->instructors->map(fn (Instructor $instructor) => $instructor->person->displayName())->implode(', ') ?: '-'),
                TextColumn::make('responses_count')->label(__('evaluation.fields.responses'))
                    ->formatStateUsing(fn (int $state, Section $record) => $state.' / '.$record->getAttribute('expected_count')),
                TextColumn::make('rate')->label(__('evaluation.fields.response_rate'))
                    ->state(fn (Section $record) => $record->getAttribute('expected_count') > 0 ? (int) floor($record->getAttribute('responses_count') * 100 / $record->getAttribute('expected_count')).'%' : '-'),
            ])
            ->defaultSort('course_id')
            ->filters([
                SelectFilter::make('term_id')->label(__('reference.resources.terms.label'))
                    ->options(fn () => Term::query()->whereIn('id', TermEvaluation::query()->select('term_id'))->orderByDesc('start_date')->get()->mapWithKeys(fn (Term $term) => [$term->id => $term->name]))
                    ->default(fn () => TermEvaluation::query()->join('terms', 'terms.id', '=', 'term_evaluations.term_id')->orderByDesc('terms.start_date')->value('term_evaluations.term_id') ?? SectionResource::defaultTermId()),
                SelectFilter::make('org_unit_id')->label(__('offering.fields.offering_unit'))->options(fn () => OrgUnitResource::unitOptions()),
            ])
            ->recordActions([
                Action::make('results')->label(__('evaluation.results.open'))->icon(Heroicon::OutlinedChartBar)->color('gray')
                    ->url(fn (Section $record) => route('portal.evaluations.results', $record))->openUrlInNewTab(),
            ]);
    }

    public static function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    public static function getPages(): array
    {
        return ['index' => ListSectionEvaluations::route('/')];
    }
}
