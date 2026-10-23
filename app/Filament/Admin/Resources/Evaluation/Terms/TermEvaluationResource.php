<?php

namespace App\Filament\Admin\Resources\Evaluation\Terms;

use App\Domain\Evaluation\Actions\ManageTermEvaluation;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Evaluation\Terms\Pages\ListTermEvaluations;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Evaluation\EvaluationForm;
use App\Models\Evaluation\TermEvaluation;
use App\Models\Term\Term;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Course evaluation per term: the form, the minimum responses for results, response
 * counts and publication to the instructors. The period is the course evaluation window
 * of each session (Terms).
 */
class TermEvaluationResource extends Resource
{
    protected static ?string $model = TermEvaluation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Evaluation;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'evaluation/terms';

    public static function getModelLabel(): string
    {
        return __('evaluation.resources.terms.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('evaluation.resources.terms.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Setting up or changing a term's evaluation.
     */
    public static function prepareAction(string $name, bool $change = false): Action
    {
        return Action::make($name)->label(__($change ? 'evaluation.actions.change' : 'evaluation.actions.prepare'))
            ->icon($change ? Heroicon::OutlinedPencilSquare : Heroicon::OutlinedPlus)
            ->authorize(fn () => Gate::allows('create', TermEvaluation::class))
            ->fillForm(fn (?TermEvaluation $record) => $record === null ? [] : $record->only(['term_id', 'evaluation_form_id', 'minimum_responses', 'minimum_response_percent']))
            ->schema([
                Select::make('term_id')->label(__('reference.resources.terms.label'))->required()
                    ->options(fn () => Term::query()->orderByDesc('start_date')->get()->mapWithKeys(fn (Term $term) => [$term->id => $term->name.' ('.$term->code.')']))
                    ->disabled(fn (?TermEvaluation $record) => $record !== null)->dehydrated(),
                Select::make('evaluation_form_id')->label(__('evaluation.resources.forms.label'))->required()
                    ->options(fn () => EvaluationForm::query()->where('is_active', true)->orderBy('code')->get()->mapWithKeys(fn (EvaluationForm $form) => [$form->id => $form->code.' '.$form->name])),
                TextInput::make('minimum_responses')->label(__('evaluation.fields.minimum_responses'))->integer()->minValue(0)->maxValue(100)
                    ->helperText(__('evaluation.help.minimum_responses')),
                TextInput::make('minimum_response_percent')->label(__('evaluation.fields.minimum_response_percent'))->integer()->minValue(0)->maxValue(100),
            ])
            ->action(fn (array $data, ManageTermEvaluation $manage) => ShowsActionErrors::run(fn () => $manage->prepare(
                Term::query()->findOrFail($data['term_id']),
                EvaluationForm::query()->findOrFail($data['evaluation_form_id']),
                self::actor(),
                filled($data['minimum_responses'] ?? null) ? (int) $data['minimum_responses'] : null,
                filled($data['minimum_response_percent'] ?? null) ? (int) $data['minimum_response_percent'] : null,
            )));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['term', 'form'])
                ->addSelect(['responses_count' => fn ($sub) => $sub->from('evaluation_responses')->selectRaw('count(*)')->whereColumn('evaluation_responses.term_id', 'term_evaluations.term_id')]))
            ->columns([
                TextColumn::make('term.name')->label(__('reference.resources.terms.label')),
                TextColumn::make('form.code')->label(__('evaluation.resources.forms.label'))->description(fn (TermEvaluation $record) => $record->form->name),
                TextColumn::make('minimum_responses')->label(__('evaluation.fields.minimum_responses'))
                    ->formatStateUsing(fn (int $state, TermEvaluation $record) => $state.' / '.$record->minimum_response_percent.'%'),
                TextColumn::make('responses_count')->label(__('evaluation.fields.responses')),
                TextColumn::make('results_published_at')->label(__('evaluation.fields.published_at'))->dateTime()->placeholder('-'),
            ])
            ->defaultSort('term_id', 'desc')
            ->recordActions([
                self::prepareAction('change', change: true),
                Action::make('publish')->label(__('evaluation.actions.publish'))->icon(Heroicon::OutlinedMegaphone)->color('success')
                    ->visible(fn (TermEvaluation $record) => $record->results_published_at === null && Gate::allows('update', $record))
                    ->requiresConfirmation()->modalDescription(__('evaluation.help.publish'))
                    ->action(fn (TermEvaluation $record, ManageTermEvaluation $manage) => $manage->publish($record, self::actor())),
            ]);
    }

    public static function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    public static function getPages(): array
    {
        return ['index' => ListTermEvaluations::route('/')];
    }
}
