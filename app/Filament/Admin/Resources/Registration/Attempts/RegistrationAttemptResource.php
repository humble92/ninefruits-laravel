<?php

namespace App\Filament\Admin\Resources\Registration\Attempts;

use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\AttemptOutcome;
use App\Domain\Registration\Enums\RuleCode;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Registration\Attempts\Pages\ListRegistrationAttempts;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\RegistrationAttempt;
use App\Models\Term\Term;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Registration attempts with each rule's result, to answer "why could I not register?".
 */
class RegistrationAttemptResource extends Resource
{
    protected static ?string $model = RegistrationAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Registration;

    protected static ?int $navigationSort = 60;

    protected static ?string $slug = 'registration/attempts';

    public static function getModelLabel(): string
    {
        return __('registration.resources.attempts.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('registration.resources.attempts.plural_label');
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User $user */
        $user = Auth::user();

        return parent::getEloquentQuery()->where(fn (Builder $query) => $query
            ->whereHas('studentRecord', fn (Builder $record) => StudentRecord::constrainVisibleTo($record, $user))
            ->orWhereHas('section', fn (Builder $section) => Section::constrainManageableBy($section, $user)));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['studentRecord', 'section.course', 'actor']))
            ->columns([
                TextColumn::make('attempted_at')->label(__('registration.fields.attempted_at'))->dateTime('Y-m-d H:i:s')->sortable(),
                TextColumn::make('studentRecord.student_number')->label(__('people.fields.student_number'))->searchable(),
                TextColumn::make('section')->label(__('offering.fields.section'))->state(fn (RegistrationAttempt $record) => $record->section?->label() ?? '-'),
                TextColumn::make('action')->label(__('registration.fields.action'))->formatStateUsing(fn (AttemptAction $state) => $state->label()),
                TextColumn::make('outcome')->label(__('registration.fields.outcome'))->formatStateUsing(fn (AttemptOutcome $state) => $state->label())->badge()
                    ->color(fn (AttemptOutcome $state) => match ($state) {
                        AttemptOutcome::Succeeded => 'success',
                        AttemptOutcome::Waitlisted => 'info',
                        AttemptOutcome::Refused => 'danger',
                    }),
                TextColumn::make('failed')->label(__('registration.fields.failed_rules'))->wrap()
                    ->state(fn (RegistrationAttempt $record) => collect($record->results)->reject(fn (array $result) => $result['passed'])
                        ->map(fn (array $result) => (RuleCode::tryFrom($result['code'])?->label() ?? $result['code']).($result['overridden'] ? ' ('.__('registration.fields.overridden').')' : '').': '.$result['message'])
                        ->implode("\n") ?: '-'),
                TextColumn::make('actor.email')->label(__('registration.fields.actor'))->placeholder('-')->toggleable(),
                TextColumn::make('request_id')->label(__('registration.fields.request_id'))->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('attempted_at', 'desc')
            ->filters([
                SelectFilter::make('term_id')->label(__('reference.resources.terms.label'))
                    ->options(fn () => Term::query()->orderByDesc('start_date')->get()->mapWithKeys(fn (Term $term) => [$term->id => $term->name])),
                SelectFilter::make('outcome')->label(__('registration.fields.outcome'))
                    ->options(collect(AttemptOutcome::cases())->mapWithKeys(fn (AttemptOutcome $outcome) => [$outcome->value => $outcome->label()])),
                SelectFilter::make('action')->label(__('registration.fields.action'))
                    ->options(collect(AttemptAction::cases())->mapWithKeys(fn (AttemptAction $action) => [$action->value => $action->label()])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListRegistrationAttempts::route('/')];
    }
}
