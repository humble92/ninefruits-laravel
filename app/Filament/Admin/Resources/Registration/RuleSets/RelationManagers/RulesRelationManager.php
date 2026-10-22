<?php

namespace App\Filament\Admin\Resources\Registration\RuleSets\RelationManagers;

use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;
use App\Models\Registration\RegistrationRule;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * The configurable rules of a set. Rules are prepared from the catalog; staff switch them
 * on or off, choose block or warning, allow overrides and set the rule's parameters.
 */
class RulesRelationManager extends RelationManager
{
    protected static string $relationship = 'rules';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('registration.resources.rules.plural_label');
    }

    public function isReadOnly(): bool
    {
        return ! Gate::allows('update', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        $is = fn (RuleCode $code) => fn (?RegistrationRule $record) => $record?->rule_code === $code;

        return $schema->columns(2)->components([
            Text::make(fn (?RegistrationRule $record) => $record?->rule_code->description() ?? '')->columnSpanFull(),
            Toggle::make('is_enabled')->label(__('registration.fields.is_enabled')),
            Toggle::make('allows_override')->label(__('registration.fields.allows_override')),
            Select::make('severity')->label(__('registration.fields.severity'))->required()
                ->options(collect(RuleSeverity::cases())->mapWithKeys(fn (RuleSeverity $severity) => [$severity->value => $severity->label()])),
            CheckboxList::make('parameters.statuses')->label(__('registration.parameters.statuses'))->visible($is(RuleCode::StudentStatus))->required()
                ->options(collect(StudentStatus::cases())->mapWithKeys(fn (StudentStatus $status) => [$status->value => $status->label()]))->columnSpanFull(),
            TextInput::make('parameters.allowed_overlap_minutes')->label(__('registration.parameters.allowed_overlap_minutes'))->visible($is(RuleCode::TimeConflict))
                ->integer()->minValue(0)->maxValue(60)->default(0),
            Toggle::make('parameters.allowed')->label(__('registration.parameters.repeat_allowed'))->visible($is(RuleCode::RepeatCourse)),
            TextInput::make('parameters.max_attempts')->label(__('registration.parameters.max_attempts'))->visible($is(RuleCode::RepeatCourse))
                ->integer()->minValue(1)->maxValue(10)->helperText(__('registration.parameters.max_attempts_help')),
            TextInput::make('parameters.below_grade')->label(__('registration.parameters.below_grade'))->visible($is(RuleCode::RepeatCourse))
                ->maxLength(4)->helperText(__('registration.parameters.below_grade_help')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rule_code')->label(__('registration.fields.rule'))->formatStateUsing(fn (RuleCode $state) => $state->label())
                    ->description(fn (RegistrationRule $record) => $record->rule_code->description())->wrap(),
                IconColumn::make('is_enabled')->label(__('registration.fields.is_enabled'))->boolean(),
                TextColumn::make('severity')->label(__('registration.fields.severity'))->formatStateUsing(fn (RuleSeverity $state) => $state->label())->badge()
                    ->color(fn (RuleSeverity $state) => $state === RuleSeverity::Block ? 'danger' : 'warning'),
                IconColumn::make('allows_override')->label(__('registration.fields.allows_override'))->boolean(),
            ])
            ->paginated(false)
            ->recordActions([
                EditAction::make()->mutateDataUsing(fn (array $data, RegistrationRule $record) => [
                    ...$data,
                    'parameters' => self::cleanParameters($record->rule_code, $data['parameters'] ?? []),
                ]),
            ]);
    }

    /**
     * Keeps the known parameters with their types; empty optional values become null.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public static function cleanParameters(RuleCode $code, array $parameters): array
    {
        return match ($code) {
            RuleCode::StudentStatus => ['statuses' => array_values((array) ($parameters['statuses'] ?? []))],
            RuleCode::TimeConflict => ['allowed_overlap_minutes' => (int) ($parameters['allowed_overlap_minutes'] ?? 0)],
            RuleCode::RepeatCourse => [
                'allowed' => (bool) ($parameters['allowed'] ?? true),
                'max_attempts' => filled($parameters['max_attempts'] ?? null) ? (int) $parameters['max_attempts'] : null,
                'below_grade' => filled($parameters['below_grade'] ?? null) ? (string) $parameters['below_grade'] : null,
            ],
            default => [],
        };
    }
}
