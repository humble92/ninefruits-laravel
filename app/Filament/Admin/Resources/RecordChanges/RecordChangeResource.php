<?php

namespace App\Filament\Admin\Resources\RecordChanges;

use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\RecordChanges\Pages\ListRecordChanges;
use App\Filament\Admin\Resources\RecordChanges\Pages\ViewRecordChange;
use App\Models\Security\RecordChange;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Data audit written by database triggers, including changes made outside the application.
 */
class RecordChangeResource extends Resource
{
    protected static ?string $model = RecordChange::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Audit;

    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return __('admin.record_changes.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.record_changes.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_at')->label(__('admin.audit.fields.occurred_at'))->dateTime()->sortable(),
                TextColumn::make('table_name')->label(__('admin.record_changes.fields.table'))->searchable(),
                TextColumn::make('operation')->label(__('admin.record_changes.fields.operation'))->badge(),
                TextColumn::make('record_id')->label(__('admin.record_changes.fields.record_id'))->searchable()->placeholder('-'),
                TextColumn::make('changed_columns')->label(__('admin.record_changes.fields.changed_columns'))
                    ->formatStateUsing(fn (RecordChange $record) => implode(', ', $record->changed_columns ?? []))
                    ->limit(40)
                    ->placeholder('-'),
                TextColumn::make('actor')->label(__('admin.audit.fields.actor'))->placeholder('-'),
                TextColumn::make('database_user')->label(__('admin.record_changes.fields.database_user')),
            ])
            ->filters([
                SelectFilter::make('table_name')->label(__('admin.record_changes.fields.table'))
                    ->options(fn () => RecordChange::query()->distinct()->orderBy('table_name')->pluck('table_name', 'table_name')->all()),
                SelectFilter::make('operation')->label(__('admin.record_changes.fields.operation'))
                    ->options(['INSERT' => 'INSERT', 'UPDATE' => 'UPDATE', 'DELETE' => 'DELETE', 'TRUNCATE' => 'TRUNCATE']),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        $format = fn (?array $values) => collect($values ?? [])
            ->map(fn ($value) => is_scalar($value) || $value === null ? $value : json_encode($value, JSON_UNESCAPED_UNICODE))
            ->all();

        return $schema->components([
            TextEntry::make('occurred_at')->label(__('admin.audit.fields.occurred_at'))->dateTime(),
            TextEntry::make('table_name')->label(__('admin.record_changes.fields.table')),
            TextEntry::make('operation')->label(__('admin.record_changes.fields.operation')),
            TextEntry::make('record_id')->label(__('admin.record_changes.fields.record_id'))->placeholder('-'),
            TextEntry::make('actor')->label(__('admin.audit.fields.actor'))->placeholder('-'),
            TextEntry::make('database_user')->label(__('admin.record_changes.fields.database_user')),
            TextEntry::make('transaction_id')->label(__('admin.record_changes.fields.transaction_id')),
            TextEntry::make('request_id')->label(__('admin.audit.fields.request_id'))->placeholder('-'),
            KeyValueEntry::make('old_values')->label(__('admin.record_changes.fields.old_values'))
                ->state(fn (RecordChange $record) => $format($record->old_values))->columnSpanFull(),
            KeyValueEntry::make('new_values')->label(__('admin.record_changes.fields.new_values'))
                ->state(fn (RecordChange $record) => $format($record->new_values))->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecordChanges::route('/'),
            'view' => ViewRecordChange::route('/{record}'),
        ];
    }
}
