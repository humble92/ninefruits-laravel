<?php

namespace App\Filament\Admin\Resources\Activities;

use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Activities\Pages\ListActivities;
use App\Filament\Admin\Resources\Activities\Pages\ViewActivity;
use App\Models\Security\Activity;
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
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Business audit (who did what and why).
 */
class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Audit;

    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return __('admin.activities.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.activities.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('causer'))
            ->columns([
                TextColumn::make('created_at')->label(__('admin.audit.fields.occurred_at'))->dateTime()->sortable(),
                TextColumn::make('log_name')->label(__('admin.audit.fields.log'))->badge(),
                TextColumn::make('event')->label(__('admin.audit.fields.event'))->searchable(),
                TextColumn::make('subject_type')->label(__('admin.audit.fields.subject'))
                    ->formatStateUsing(fn (Activity $record) => $record->subject_type === null ? null : class_basename($record->subject_type).' #'.$record->subject_id)
                    ->placeholder('-'),
                TextColumn::make('causer.name')->label(__('admin.audit.fields.actor'))->placeholder('-'),
                TextColumn::make('reason')->label(__('common.reason'))->limit(40)->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('log_name')->label(__('admin.audit.fields.log'))
                    ->options(fn () => Activity::query()->distinct()->orderBy('log_name')->pluck('log_name', 'log_name')->filter()->all()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->label(__('admin.audit.fields.occurred_at'))->dateTime(),
            TextEntry::make('event')->label(__('admin.audit.fields.event')),
            TextEntry::make('causer.name')->label(__('admin.audit.fields.actor'))->placeholder('-'),
            TextEntry::make('subject_type')->label(__('admin.audit.fields.subject'))
                ->formatStateUsing(fn (Activity $record) => $record->subject_type === null ? null : class_basename($record->subject_type).' #'.$record->subject_id)
                ->placeholder('-'),
            TextEntry::make('reason')->label(__('common.reason'))->placeholder('-')->columnSpanFull(),
            KeyValueEntry::make('properties')->label(__('admin.audit.fields.properties'))
                ->state(fn (Activity $record) => collect($record->properties?->all() ?? [])->map(fn ($value) => is_scalar($value) || $value === null ? $value : json_encode($value, JSON_UNESCAPED_UNICODE))->all())
                ->columnSpanFull(),
            TextEntry::make('request_id')->label(__('admin.audit.fields.request_id'))->placeholder('-'),
            TextEntry::make('ip_address')->label(__('admin.audit.fields.ip_address'))->placeholder('-'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
            'view' => ViewActivity::route('/{record}'),
        ];
    }
}
