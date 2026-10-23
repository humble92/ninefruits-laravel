<?php

namespace App\Filament\Admin\Resources\Graduation\Waivers;

use App\Domain\Graduation\Actions\WaiveRequirement;
use App\Domain\Graduation\Enums\WaiverStatus;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Graduation\Waivers\Pages\ListDegreeWaivers;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Graduation\DegreeRequirementWaiver;
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
 * Requirement waivers: requested by one person, approved or rejected by another, lifted
 * with a reason. The audit counts approved ones only.
 */
class DegreeWaiverResource extends Resource
{
    protected static ?string $model = DegreeRequirementWaiver::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Graduation;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'graduation/waivers';

    public static function getModelLabel(): string
    {
        return __('graduation.resources.waivers.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('graduation.resources.waivers.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = DegreeRequirementWaiver::query()->where('status', WaiverStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('studentRecord', fn (Builder $record) => StudentRecord::constrainVisibleTo($record, self::actor()));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['studentRecord.person', 'requirement.assessmentType', 'requestedBy', 'decidedBy']))
            ->columns([
                TextColumn::make('requested_at')->label(__('grading.fields.requested_at'))->dateTime()->sortable(),
                TextColumn::make('studentRecord.student_number')->label(__('people.fields.student_number'))->searchable()
                    ->description(fn (DegreeRequirementWaiver $record) => $record->studentRecord->person->displayName()),
                TextColumn::make('requirement')->label(__('curriculum.sections.requirements'))
                    ->state(fn (DegreeRequirementWaiver $record) => $record->requirement->name ?? ($record->requirement->assessmentType !== null ? $record->requirement->assessmentType->name : $record->requirement->kind->label())),
                TextColumn::make('reason')->label(__('common.reason'))->limit(50)->wrap(),
                TextColumn::make('requestedBy.email')->label(__('grading.fields.requested_by')),
                TextColumn::make('status')->label(__('registration.fields.status'))->badge()->formatStateUsing(fn (WaiverStatus $state) => $state->label())
                    ->color(fn (WaiverStatus $state) => match ($state) {
                        WaiverStatus::Pending => 'warning', WaiverStatus::Approved => 'success', WaiverStatus::Rejected => 'danger', WaiverStatus::Lifted => 'gray'
                    }),
                TextColumn::make('decidedBy.email')->label(__('grading.fields.decided_by'))->placeholder('-')->toggleable(),
                TextColumn::make('decision_note')->label(__('grading.fields.decision_note'))->placeholder('-')->limit(40)->toggleable(),
                TextColumn::make('lift_reason')->label(__('graduation.fields.lift_reason'))->placeholder('-')->limit(40)->toggleable(),
            ])
            ->defaultSort('requested_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label(__('registration.fields.status'))->default(WaiverStatus::Pending->value)
                    ->options(collect(WaiverStatus::cases())->mapWithKeys(fn (WaiverStatus $status) => [$status->value => $status->label()])),
            ])
            ->recordActions([
                Action::make('approve')->label(__('grading.actions.approve'))->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (DegreeRequirementWaiver $record) => $record->status === WaiverStatus::Pending && $record->requested_by_id !== self::actor()->id && self::actor()->can('graduation.process'))
                    ->schema([Textarea::make('note')->label(__('grading.fields.decision_note'))->maxLength(1000)])
                    ->action(fn (DegreeRequirementWaiver $record, array $data, WaiveRequirement $waive) => ShowsActionErrors::run(fn () => $waive->approve($record, self::actor(), $data['note'] ?? null))),
                Action::make('reject')->label(__('grading.actions.reject'))->icon(Heroicon::OutlinedXMark)->color('danger')
                    ->visible(fn (DegreeRequirementWaiver $record) => $record->status === WaiverStatus::Pending && $record->requested_by_id !== self::actor()->id && self::actor()->can('graduation.process'))
                    ->schema([Textarea::make('note')->label(__('grading.fields.decision_note'))->required()->maxLength(1000)])
                    ->action(fn (DegreeRequirementWaiver $record, array $data, WaiveRequirement $waive) => ShowsActionErrors::run(fn () => $waive->reject($record, self::actor(), (string) $data['note']))),
                Action::make('lift')->label(__('graduation.actions.lift'))->icon(Heroicon::OutlinedArrowUturnLeft)->color('gray')
                    ->visible(fn (DegreeRequirementWaiver $record) => $record->status === WaiverStatus::Approved && self::actor()->can('graduation.process'))
                    ->schema([Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000)])
                    ->action(fn (DegreeRequirementWaiver $record, array $data, WaiveRequirement $waive) => ShowsActionErrors::run(fn () => $waive->lift($record, self::actor(), (string) $data['reason']))),
            ]);
    }

    public static function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    public static function getPages(): array
    {
        return ['index' => ListDegreeWaivers::route('/')];
    }
}
