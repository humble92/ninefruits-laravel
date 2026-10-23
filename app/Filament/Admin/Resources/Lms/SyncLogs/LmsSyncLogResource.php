<?php

namespace App\Filament\Admin\Resources\Lms\SyncLogs;

use App\Domain\Lms\Enums\SyncOperation;
use App\Domain\Lms\Enums\SyncStatus;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Lms\SyncLogs\Pages\ListLmsSyncLogs;
use App\Models\Lms\LmsPlatform;
use App\Models\Lms\LmsSyncLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Every synchronization step and its outcome.
 */
class LmsSyncLogResource extends Resource
{
    protected static ?string $model = LmsSyncLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Lms;

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'lms/sync-logs';

    public static function getModelLabel(): string
    {
        return __('lms.resources.sync_logs.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lms.resources.sync_logs.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['platform', 'section.course', 'user']))
            ->columns([
                TextColumn::make('started_at')->label(__('lms.fields.started_at'))->dateTime()->sortable(),
                TextColumn::make('platform.code')->label(__('lms.resources.platforms.label')),
                TextColumn::make('operation')->label(__('lms.fields.operation'))->formatStateUsing(fn (SyncOperation $state) => $state->label()),
                TextColumn::make('section')->label(__('offering.resources.sections.label'))->state(fn (LmsSyncLog $record) => $record->section?->label())->placeholder('-'),
                TextColumn::make('user.email')->label(__('admin.users.label'))->placeholder('-')->toggleable(),
                TextColumn::make('status')->label(__('registration.fields.status'))->badge()->formatStateUsing(fn (SyncStatus $state) => $state->label())
                    ->color(fn (SyncStatus $state) => match ($state) {
                        SyncStatus::Succeeded => 'success', SyncStatus::Failed => 'danger', SyncStatus::Skipped => 'gray', SyncStatus::Queued => 'warning'
                    }),
                TextColumn::make('message')->label(__('lms.fields.message'))->placeholder('-')->limit(60)->wrap(),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                SelectFilter::make('lms_platform_id')->label(__('lms.resources.platforms.label'))->options(fn () => LmsPlatform::query()->orderBy('code')->pluck('code', 'id')),
                SelectFilter::make('operation')->label(__('lms.fields.operation'))->options(collect(SyncOperation::cases())->mapWithKeys(fn (SyncOperation $o) => [$o->value => $o->label()])),
                SelectFilter::make('status')->label(__('registration.fields.status'))->options(collect(SyncStatus::cases())->mapWithKeys(fn (SyncStatus $s) => [$s->value => $s->label()])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListLmsSyncLogs::route('/')];
    }
}
