<?php

namespace App\Filament\Admin\Resources\Registration\CreditLimitPolicies;

use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Registration\CreditLimitPolicies\Pages\ManageCreditLimitPolicies;
use App\Filament\Admin\Support\TargetCriteriaFields;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Registration\CreditLimitPolicy;
use App\Models\Term\TermType;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Credit limits per term type and student group. The active policy with the highest
 * priority whose eligibility matches the student applies (legacy limits hard-coded per
 * admission year and department become rows here).
 */
class CreditLimitPolicyResource extends Resource
{
    protected static ?string $model = CreditLimitPolicy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Registration;

    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'registration/credit-limit-policies';

    public static function getModelLabel(): string
    {
        return __('registration.resources.credit_limit_policies.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('registration.resources.credit_limit_policies.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TranslatableFields::make('name', __('reference.fields.name'))->columnSpanFull(),
            Select::make('term_type_id')->label(__('reference.resources.term_types.label'))->placeholder(__('registration.fields.all_term_types'))
                ->options(fn () => TermType::query()->orderBy('sort_order')->get()->mapWithKeys(fn (TermType $type) => [$type->id => $type->name])),
            TextInput::make('priority')->label(__('registration.fields.priority'))->integer()->default(0)->required()->helperText(__('registration.help.priority')),
            TextInput::make('min_credits')->label(__('registration.fields.min_credits'))->numeric()->minValue(0)->maxValue(99)->lte('max_credits'),
            TextInput::make('max_credits')->label(__('registration.fields.max_credits'))->numeric()->minValue(0)->maxValue(99)->required(),
            Toggle::make('is_active')->label(__('registration.fields.is_active'))->default(true),
            TargetCriteriaFields::make('eligibility', __('registration.fields.applies_to'), __('registration.help.credit_policy_eligibility'))->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('termType'))
            ->columns([
                TextColumn::make('name')->label(__('reference.fields.name')),
                TextColumn::make('termType.name')->label(__('reference.resources.term_types.label'))->placeholder(__('registration.fields.all_term_types')),
                TextColumn::make('eligibility')->label(__('registration.fields.applies_to'))->state(fn (CreditLimitPolicy $record) => TargetCriteriaFields::summary($record->eligibility) ?: '-')->wrap(),
                TextColumn::make('max_credits')->label(__('registration.fields.max_credits')),
                TextColumn::make('priority')->label(__('registration.fields.priority'))->sortable(),
                IconColumn::make('is_active')->label(__('registration.fields.is_active'))->boolean(),
            ])
            ->defaultSort('priority', 'desc')
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, CreditLimitPolicy $record) => [...$data, 'name' => $record->getTranslations('name'), 'eligibility' => TargetCriteriaFields::toState($record->eligibility)])
                    ->mutateDataUsing(fn (array $data) => self::fromForm($data)),
            ]);
    }

    public static function createAction(): CreateAction
    {
        return CreateAction::make()->mutateDataUsing(fn (array $data) => self::fromForm($data));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function fromForm(array $data): array
    {
        return [...$data, 'min_credits' => filled($data['min_credits'] ?? null) ? $data['min_credits'] : null, 'eligibility' => TargetCriteriaFields::fromState($data['eligibility'] ?? null)];
    }

    public static function getPages(): array
    {
        return ['index' => ManageCreditLimitPolicies::route('/')];
    }
}
