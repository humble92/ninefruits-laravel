<?php

namespace App\Filament\Admin\Resources\Assessment\Results;

use App\Domain\Assessment\Actions\RecordAssessmentResult;
use App\Domain\Assessment\Enums\ResultSource;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Assessment\Results\Pages\ListAssessmentResults;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Assessment\AssessmentResult;
use App\Models\Assessment\AssessmentType;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Every result, from sessions and from outside bodies: the ledger behind the
 * "passed examination" graduation requirement (legacy SSCORE_ADD, COMEXAM, CET rows).
 */
class AssessmentResultResource extends Resource
{
    protected static ?string $model = AssessmentResult::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Assessments;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'assessments/results';

    public static function getModelLabel(): string
    {
        return __('assessment.resources.results.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('assessment.resources.results.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('studentRecord', fn (Builder $record) => StudentRecord::constrainVisibleTo($record, self::actor()));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['studentRecord.person', 'type', 'session', 'componentScores.component', 'media']))
            ->columns([
                TextColumn::make('taken_date')->label(__('assessment.fields.taken_date'))->date()->sortable(),
                TextColumn::make('type.name')->label(__('assessment.fields.type'))->description(fn (AssessmentResult $record) => $record->source->label()),
                TextColumn::make('studentRecord.student_number')->label(__('people.fields.student_number'))->searchable(),
                TextColumn::make('name')->label(__('people.fields.name'))->state(fn (AssessmentResult $record) => $record->studentRecord->person->displayName())
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('studentRecord.person', fn (Builder $person) => Person::constrainToSearch($person, $search))),
                TextColumn::make('total_score')->label(__('assessment.fields.total_score'))->placeholder('-')
                    ->description(fn (AssessmentResult $record) => $record->componentScores->map(fn ($score) => $score->component->code.' '.$score->score)->implode(' · ')),
                TextColumn::make('level')->label(__('assessment.fields.level'))->placeholder('-'),
                IconColumn::make('is_passed')->label(__('assessment.fields.is_passed'))->boolean(),
                TextColumn::make('certificate_number')->label(__('assessment.fields.certificate_number'))->placeholder('-')->toggleable(),
                IconColumn::make('evidence')->label(__('assessment.fields.evidence'))->state(fn (AssessmentResult $record) => $record->getMedia(AssessmentResult::EVIDENCE_COLLECTION)->isNotEmpty())->boolean(),
            ])
            ->defaultSort('taken_date', 'desc')
            ->filters([
                SelectFilter::make('assessment_type_id')->label(__('assessment.fields.type'))
                    ->options(fn () => AssessmentType::query()->orderBy('sort_order')->get()->pluck('name', 'id')),
                SelectFilter::make('source')->label(__('assessment.fields.source'))
                    ->options(collect(ResultSource::cases())->mapWithKeys(fn (ResultSource $source) => [$source->value => $source->label()])),
                TernaryFilter::make('is_passed')->label(__('assessment.fields.is_passed')),
            ])
            ->headerActions([
                Action::make('external')->label(__('assessment.actions.record_external'))->icon(Heroicon::OutlinedDocumentArrowUp)
                    ->visible(fn () => self::actor()->can('assessments.record'))
                    ->schema([
                        TextInput::make('student_number')->label(__('people.fields.student_number'))->required(),
                        Select::make('assessment_type_id')->label(__('assessment.fields.type'))->required()
                            ->options(fn () => AssessmentType::query()->where('is_active', true)->where('allows_external', true)->orderBy('sort_order')->get()->pluck('name', 'id')),
                        DatePicker::make('taken_date')->label(__('assessment.fields.taken_date'))->required()->maxDate(now()),
                        TextInput::make('total')->label(__('assessment.fields.total_score'))->numeric()->minValue(0),
                        TextInput::make('level')->label(__('assessment.fields.level'))->maxLength(32),
                        Select::make('passed')->label(__('assessment.fields.is_passed'))->required()->options(['1' => __('assessment.passed.yes'), '0' => __('assessment.passed.no')]),
                        TextInput::make('certificate_number')->label(__('assessment.fields.certificate_number'))->maxLength(64),
                        FileUpload::make('evidence')->label(__('assessment.fields.evidence'))->storeFiles(false)->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])->maxSize(5120),
                        Textarea::make('note')->label(__('assessment.fields.note'))->maxLength(1000),
                    ])
                    ->action(function (array $data, RecordAssessmentResult $results) {
                        $record = StudentRecord::query()->where('student_number', trim((string) $data['student_number']))->first() ?? abort(404);
                        $evidence = $data['evidence'] ?? null;
                        ShowsActionErrors::run(fn () => $results->external(
                            $record, AssessmentType::query()->findOrFail($data['assessment_type_id']), CarbonImmutable::parse((string) $data['taken_date']), (bool) $data['passed'], self::actor(),
                            filled($data['total'] ?? null) ? (float) $data['total'] : null, filled($data['level'] ?? null) ? (string) $data['level'] : null,
                            filled($data['certificate_number'] ?? null) ? (string) $data['certificate_number'] : null, filled($data['note'] ?? null) ? (string) $data['note'] : null,
                            $evidence instanceof UploadedFile ? $evidence : null,
                        ));
                    }),
            ])
            ->recordActions([
                Action::make('evidence')->label(__('assessment.fields.evidence'))->icon(Heroicon::OutlinedPaperClip)->color('gray')
                    ->visible(fn (AssessmentResult $record) => $record->getMedia(AssessmentResult::EVIDENCE_COLLECTION)->isNotEmpty())
                    ->url(fn (AssessmentResult $record) => route('assessments.evidence', $record))->openUrlInNewTab(),
                Action::make('remove')->label(__('assessment.actions.remove'))->icon(Heroicon::OutlinedTrash)->color('danger')
                    ->visible(fn () => self::actor()->can('assessments.record'))
                    ->schema([Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000)])
                    ->action(fn (AssessmentResult $record, array $data, RecordAssessmentResult $results) => ShowsActionErrors::run(fn () => $results->remove($record, self::actor(), $data['reason']))),
            ]);
    }

    public static function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    public static function getPages(): array
    {
        return ['index' => ListAssessmentResults::route('/')];
    }
}
