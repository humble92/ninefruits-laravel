<?php

namespace App\Filament\Admin\Resources\Assessment\Corrections;

use App\Domain\Assessment\Actions\CorrectAssessmentResult;
use App\Domain\Assessment\Enums\CorrectionStatus;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Assessment\Corrections\Pages\ListAssessmentCorrections;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Assessment\AssessmentResultCorrection;
use App\Models\Person\StudentRecord;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Correction requests for published results: approved (applied) or rejected by someone
 * other than the requester, with assessments.manage.
 */
class AssessmentCorrectionResource extends Resource
{
    protected static ?string $model = AssessmentResultCorrection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Assessments;

    protected static ?int $navigationSort = 25;

    protected static ?string $slug = 'assessments/corrections';

    public static function getModelLabel(): string
    {
        return __('assessment.resources.corrections.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('assessment.resources.corrections.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = AssessmentResultCorrection::query()->pending()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('result.studentRecord', fn (Builder $record) => StudentRecord::constrainVisibleTo($record, self::actor()));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['result.studentRecord.person', 'result.type', 'result.session', 'requestedBy', 'decidedBy']))
            ->columns([
                TextColumn::make('requested_at')->label(__('grading.fields.requested_at'))->dateTime()->sortable(),
                TextColumn::make('result.session.held_date')->label(__('assessment.fields.type'))->date()
                    ->description(fn (AssessmentResultCorrection $record) => $record->result->type->name),
                TextColumn::make('result.studentRecord.student_number')->label(__('people.fields.student_number'))->searchable()
                    ->description(fn (AssessmentResultCorrection $record) => $record->result->studentRecord->person->displayName()),
                TextColumn::make('previous')->label(__('assessment.fields.previous'))->state(fn (AssessmentResultCorrection $record) => self::describe($record->previous))->wrap(),
                TextColumn::make('proposed')->label(__('assessment.fields.proposed'))->state(fn (AssessmentResultCorrection $record) => self::describe($record->proposed))->wrap(),
                TextColumn::make('reason')->label(__('common.reason'))->limit(40)->wrap(),
                TextColumn::make('requestedBy.email')->label(__('grading.fields.requested_by')),
                TextColumn::make('status')->label(__('registration.fields.status'))->badge()->formatStateUsing(fn (CorrectionStatus $state) => $state->label())
                    ->color(fn (CorrectionStatus $state) => match ($state) {
                        CorrectionStatus::Pending => 'warning', CorrectionStatus::Approved => 'success', CorrectionStatus::Rejected => 'danger', CorrectionStatus::Cancelled => 'gray',
                    }),
                TextColumn::make('decision_note')->label(__('grading.fields.decision_note'))->placeholder('-')->limit(40)->toggleable(),
            ])
            ->defaultSort('requested_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label(__('registration.fields.status'))->default(CorrectionStatus::Pending->value)
                    ->options(collect(CorrectionStatus::cases())->mapWithKeys(fn (CorrectionStatus $status) => [$status->value => $status->label()])),
            ])
            ->recordActions([
                Action::make('approve')->label(__('grading.actions.approve'))->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (AssessmentResultCorrection $record) => $record->status === CorrectionStatus::Pending && self::actor()->can('assessments.manage') && $record->requested_by_id !== self::actor()->id)
                    ->schema([Textarea::make('note')->label(__('grading.fields.decision_note'))->maxLength(1000)])
                    ->action(fn (AssessmentResultCorrection $record, array $data, CorrectAssessmentResult $corrections) => ShowsActionErrors::run(fn () => $corrections->approve($record, self::actor(), $data['note'] ?? null))),
                Action::make('reject')->label(__('grading.actions.reject'))->icon(Heroicon::OutlinedXMark)->color('danger')
                    ->visible(fn (AssessmentResultCorrection $record) => $record->status === CorrectionStatus::Pending && self::actor()->can('assessments.manage') && $record->requested_by_id !== self::actor()->id)
                    ->schema([Textarea::make('note')->label(__('grading.fields.decision_note'))->required()->maxLength(1000)])
                    ->action(fn (AssessmentResultCorrection $record, array $data, CorrectAssessmentResult $corrections) => ShowsActionErrors::run(fn () => $corrections->reject($record, self::actor(), (string) $data['note']))),
                Action::make('cancel')->label(__('grading.actions.cancel_request'))->icon(Heroicon::OutlinedNoSymbol)->color('gray')
                    ->visible(fn (AssessmentResultCorrection $record) => $record->status === CorrectionStatus::Pending && $record->requested_by_id === self::actor()->id)
                    ->requiresConfirmation()
                    ->action(fn (AssessmentResultCorrection $record, CorrectAssessmentResult $corrections) => ShowsActionErrors::run(fn () => $corrections->cancel($record, self::actor()))),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function describe(array $data): string
    {
        $parts = [];

        foreach ((array) ($data['scores'] ?? []) as $code => $score) {
            $parts[] = "{$code} {$score}";
        }

        $text = implode(' · ', $parts);
        $text .= isset($data['total']) ? ($text === '' ? '' : ' = ').$data['total'] : '';

        return $text.(isset($data['passed']) ? ' · '.($data['passed'] ? __('assessment.passed.yes') : __('assessment.passed.no')) : '');
    }

    public static function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    public static function getPages(): array
    {
        return ['index' => ListAssessmentCorrections::route('/')];
    }
}
