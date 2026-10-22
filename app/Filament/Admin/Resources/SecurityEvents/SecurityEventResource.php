<?php

namespace App\Filament\Admin\Resources\SecurityEvents;

use App\Domain\Security\Enums\SecurityEventType;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\SecurityEvents\Pages\ListSecurityEvents;
use App\Models\Security\SecurityEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SecurityEventResource extends Resource
{
    protected static ?string $model = SecurityEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Audit;

    protected static ?int $navigationSort = 30;

    public static function getModelLabel(): string
    {
        return __('admin.security_events.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.security_events.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                TextColumn::make('occurred_at')->label(__('admin.audit.fields.occurred_at'))->dateTime()->sortable(),
                TextColumn::make('type')->label(__('admin.security_events.fields.type'))
                    ->formatStateUsing(fn (SecurityEvent $record) => $record->type->label())
                    ->badge(),
                TextColumn::make('user.name')->label(__('admin.audit.fields.actor'))->placeholder('-'),
                TextColumn::make('identifier')->label(__('admin.security_events.fields.identifier'))->searchable()->placeholder('-'),
                TextColumn::make('subject_type')->label(__('admin.audit.fields.subject'))
                    ->formatStateUsing(fn (SecurityEvent $record) => $record->subject_type === null ? null : class_basename($record->subject_type).' #'.$record->subject_id)
                    ->placeholder('-'),
                TextColumn::make('ip_address')->label(__('admin.audit.fields.ip_address'))->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('type')->label(__('admin.security_events.fields.type'))
                    ->options(collect(SecurityEventType::cases())->mapWithKeys(fn (SecurityEventType $type) => [$type->value => $type->label()])),
            ])
            ->defaultSort('occurred_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSecurityEvents::route('/'),
        ];
    }
}
