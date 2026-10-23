<?php

namespace App\Filament\Admin\Resources\Assessment\Sessions\RelationManagers;

use App\Domain\Assessment\Actions\CorrectAssessmentResult;
use App\Domain\Assessment\Actions\RecordAssessmentResult;
use App\Domain\Assessment\Actions\RegisterForAssessment;
use App\Domain\Assessment\Enums\AssessmentRegistrationStatus;
use App\Domain\Assessment\Enums\ScoringMethod;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Assessment\AssessmentComponent;
use App\Models\Assessment\AssessmentRegistration;
use App\Models\Assessment\AssessmentSession;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * The registrants of a session with their results (legacy cet_regstu, cet_add2, cet_edit).
 */
class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('assessment.fields.registrants');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['studentRecord.person', 'result.componentScores.component']))
            ->columns([
                TextColumn::make('studentRecord.student_number')->label(__('people.fields.student_number'))->searchable(),
                TextColumn::make('name')->label(__('people.fields.name'))->state(fn (AssessmentRegistration $record) => $record->studentRecord->person->displayName())
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('studentRecord.person', fn (Builder $person) => Person::constrainToSearch($person, $search))),
                TextColumn::make('status')->label(__('registration.fields.status'))->badge()->formatStateUsing(fn (AssessmentRegistrationStatus $state) => $state->label())
                    ->color(fn (AssessmentRegistrationStatus $state) => match ($state) {
                        AssessmentRegistrationStatus::Registered => 'success', AssessmentRegistrationStatus::Absent => 'warning', AssessmentRegistrationStatus::Cancelled => 'gray',
                    }),
                TextColumn::make('result.total_score')->label(__('assessment.fields.total_score'))->placeholder('-')
                    ->description(fn (AssessmentRegistration $record) => $record->result?->componentScores->map(fn ($score) => $score->component->code.' '.$score->score)->implode(' · ')),
                TextColumn::make('result.is_passed')->label(__('assessment.fields.is_passed'))->badge()->placeholder('-')
                    ->formatStateUsing(fn (bool $state) => $state ? __('assessment.passed.yes') : __('assessment.passed.no'))->color(fn (bool $state) => $state ? 'success' : 'danger'),
                TextColumn::make('registered_at')->label(__('assessment.fields.registered_at'))->dateTime()->toggleable(),
            ])
            ->defaultSort('registered_at')
            ->filters([
                SelectFilter::make('status')->label(__('registration.fields.status'))
                    ->options(collect(AssessmentRegistrationStatus::cases())->mapWithKeys(fn (AssessmentRegistrationStatus $status) => [$status->value => $status->label()])),
            ])
            ->headerActions([
                Action::make('register')->label(__('assessment.actions.register_student'))->icon(Heroicon::OutlinedUserPlus)
                    ->visible(fn () => $this->actor()->can('assessments.record') && $this->session()->held_date->gte(CarbonImmutable::today()))
                    ->schema([TextInput::make('student_number')->label(__('people.fields.student_number'))->required()])
                    ->action(function (array $data, RegisterForAssessment $register) {
                        $record = StudentRecord::query()->where('student_number', trim((string) $data['student_number']))->first();
                        ShowsActionErrors::run(fn () => $register->register($this->session(), $record ?? abort(404), $this->actor(), byStaff: true));
                    }),
            ])
            ->recordActions([
                Action::make('result')->label(__('assessment.actions.enter_result'))->icon(Heroicon::OutlinedPencilSquare)
                    ->visible(fn (AssessmentRegistration $record) => $record->status !== AssessmentRegistrationStatus::Cancelled && $this->session()->results_published_at === null && $this->actor()->can('assessments.record'))
                    ->fillForm(fn (AssessmentRegistration $record) => [
                        'scores' => $record->result?->componentScores->mapWithKeys(fn ($score) => [$score->component->code => $score->score])->all() ?? [],
                        'total' => $record->result?->total_score, 'passed' => $record->result === null ? null : ($record->result->is_passed ? '1' : '0'), 'note' => $record->result?->note,
                    ])
                    ->schema(fn () => $this->resultForm())
                    ->action(fn (AssessmentRegistration $record, array $data, RecordAssessmentResult $results) => ShowsActionErrors::run(fn () => $results->forRegistration(
                        $record, $data['scores'] ?? [], filled($data['passed'] ?? null) ? (bool) $data['passed'] : null, $this->actor(),
                        filled($data['total'] ?? null) ? (float) $data['total'] : null, $data['note'] ?? null,
                    ))),
                Action::make('correct')->label(__('assessment.actions.request_correction'))->icon(Heroicon::OutlinedPencilSquare)->color('warning')
                    ->visible(fn (AssessmentRegistration $record) => $record->result !== null && $this->session()->results_published_at !== null && $this->actor()->can('assessments.record'))
                    ->modalDescription(__('assessment.help.correction'))
                    ->fillForm(fn (AssessmentRegistration $record) => [
                        'scores' => $record->result?->componentScores->mapWithKeys(fn ($score) => [$score->component->code => $score->score])->all() ?? [],
                        'total' => $record->result?->total_score, 'passed' => $record->result === null ? null : ($record->result->is_passed ? '1' : '0'), 'note' => $record->result?->note,
                    ])
                    ->schema(fn () => [...$this->resultForm(), Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000)])
                    ->action(fn (AssessmentRegistration $record, array $data, CorrectAssessmentResult $corrections) => ShowsActionErrors::run(fn () => $corrections->request(
                        $record->result ?? abort(404),
                        ['scores' => $data['scores'] ?? [], 'total' => filled($data['total'] ?? null) ? (float) $data['total'] : null, 'passed' => filled($data['passed'] ?? null) ? (bool) $data['passed'] : null, 'note' => $data['note'] ?? null],
                        (string) $data['reason'], $this->actor(),
                    ))),
                Action::make('absent')->label(__('assessment.actions.absent'))->icon(Heroicon::OutlinedUserMinus)->color('warning')
                    ->visible(fn (AssessmentRegistration $record) => $record->status === AssessmentRegistrationStatus::Registered && $record->result === null && $this->actor()->can('assessments.record'))
                    ->requiresConfirmation()
                    ->action(fn (AssessmentRegistration $record, RegisterForAssessment $register) => ShowsActionErrors::run(fn () => $register->markAbsent($record, $this->actor()))),
                Action::make('cancel')->label(__('assessment.actions.cancel'))->icon(Heroicon::OutlinedXMark)->color('danger')
                    ->visible(fn (AssessmentRegistration $record) => $record->status !== AssessmentRegistrationStatus::Cancelled && $record->result === null && $this->actor()->can('assessments.record'))
                    ->schema([Textarea::make('reason')->label(__('common.reason'))->maxLength(1000)])
                    ->action(fn (AssessmentRegistration $record, array $data, RegisterForAssessment $register) => ShowsActionErrors::run(fn () => $register->cancel($record, $this->actor(), $data['reason'] ?? null))),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    private function resultForm(): array
    {
        $session = $this->session();
        $type = $session->type;
        $fields = $type->components->map(fn (AssessmentComponent $component) => TextInput::make("scores.{$component->code}")
            ->label($component->name.' (/'.$component->max_score.')')->numeric()->minValue(0)->maxValue((float) $component->max_score)->required())->all();

        if ($fields === []) {
            $fields[] = TextInput::make('total')->label(__('assessment.fields.total_score'))->numeric()->minValue(0)
                ->required($type->scoring === ScoringMethod::Score && $session->passScore() !== null);
        }

        $decision = Select::make('passed')->label(__('assessment.fields.is_passed'))->options(['1' => __('assessment.passed.yes'), '0' => __('assessment.passed.no')])
            ->required($type->scoring === ScoringMethod::PassFail || $session->passScore() === null)
            ->helperText($type->scoring === ScoringMethod::Score && $session->passScore() !== null ? __('assessment.help.decision_automatic', ['score' => $session->passScore()]) : null);

        return [Grid::make(2)->schema($fields), $decision, Textarea::make('note')->label(__('assessment.fields.note'))->maxLength(1000)];
    }

    private function session(): AssessmentSession
    {
        /** @var AssessmentSession $session */
        $session = $this->getOwnerRecord();

        return $session->loadMissing('type.components');
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
