<?php

namespace App\Filament\Admin\Resources\Registration\RuleSets;

use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Registration\RuleSets\Pages\EditRegistrationRuleSet;
use App\Filament\Admin\Resources\Registration\RuleSets\Pages\ListRegistrationRuleSets;
use App\Filament\Admin\Resources\Registration\RuleSets\RelationManagers\RulesRelationManager;
use App\Models\Registration\RegistrationRuleSet;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Registration rules per term (legacy rules hard-coded per school become settings).
 * Window, section state and seats always apply and are not listed.
 */
class RegistrationRuleSetResource extends Resource
{
    protected static ?string $model = RegistrationRuleSet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Registration;

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'registration/rule-sets';

    public static function getModelLabel(): string
    {
        return __('registration.resources.rule_sets.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('registration.resources.rule_sets.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof RegistrationRuleSet ? $record->term->name : '';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Textarea::make('note')->label(__('offering.fields.note'))->maxLength(2000),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['term', 'copiedFrom.term'])
                ->withCount(['rules as enabled_rules_count' => fn (Builder $rule) => $rule->where('is_enabled', true)]))
            ->columns([
                TextColumn::make('term.name')->label(__('reference.resources.terms.label')),
                TextColumn::make('term.code')->label(__('reference.fields.code'))->badge()->color('gray'),
                TextColumn::make('enabled_rules_count')->label(__('registration.fields.enabled_rules')),
                TextColumn::make('copiedFrom.term.name')->label(__('registration.fields.copied_from'))->placeholder('-'),
                TextColumn::make('updated_at')->label(__('registration.fields.updated_at'))->dateTime(),
            ])
            ->defaultSort('term_id', 'desc')
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [RulesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegistrationRuleSets::route('/'),
            'edit' => EditRegistrationRuleSet::route('/{record}/edit'),
        ];
    }
}
