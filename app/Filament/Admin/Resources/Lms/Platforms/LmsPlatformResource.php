<?php

namespace App\Filament\Admin\Resources\Lms\Platforms;

use App\Domain\Lms\Enums\LmsDriver;
use App\Domain\Lms\Services\ConnectorRegistry;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Lms\Platforms\Pages\CreateLmsPlatform;
use App\Filament\Admin\Resources\Lms\Platforms\Pages\EditLmsPlatform;
use App\Filament\Admin\Resources\Lms\Platforms\Pages\ListLmsPlatforms;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Lms\LmsPlatform;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
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
 * The LMS platforms (docs/lms-integration-guide.md). Credentials are written, never shown.
 */
class LmsPlatformResource extends Resource
{
    protected static ?string $model = LmsPlatform::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Lms;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'lms/platforms';

    public static function getModelLabel(): string
    {
        return __('lms.resources.platforms.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lms.resources.platforms.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(32)->alphaDash()->unique(ignoreRecord: true),
            Select::make('driver')->label(__('lms.fields.driver'))->required()->default(LmsDriver::Null->value)
                ->options(collect(LmsDriver::cases())->mapWithKeys(fn (LmsDriver $driver) => [$driver->value => $driver->label().(ConnectorRegistry::isImplemented($driver) ? '' : ' ('.__('lms.fields.not_implemented').')')]))
                ->helperText(__('lms.help.driver')),
            TranslatableFields::make('name', __('reference.fields.name'))->columnSpanFull(),
            TextInput::make('base_url')->label(__('lms.fields.base_url'))->url()->maxLength(255)->columnSpanFull(),
            KeyValue::make('credentials')->label(__('lms.fields.credentials'))->keyLabel(__('lms.fields.credential_key'))->valueLabel(__('lms.fields.credential_value'))
                ->helperText(__('lms.help.credentials'))->columnSpanFull()->dehydrated(fn ($state) => filled($state)),
            Toggle::make('is_active')->label(__('reference.fields.is_active'))->helperText(__('lms.help.is_active')),
            Toggle::make('should_sync_enrollments')->label(__('lms.fields.should_sync_enrollments'))->default(true),
            Toggle::make('should_pull_grades')->label(__('lms.fields.should_pull_grades'))->helperText(__('lms.help.should_pull_grades')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable(),
                TextColumn::make('name')->label(__('reference.fields.name')),
                TextColumn::make('driver')->label(__('lms.fields.driver'))->formatStateUsing(fn (LmsDriver $state) => $state->label()),
                TextColumn::make('base_url')->label(__('lms.fields.base_url'))->placeholder('-')->limit(40),
                TextColumn::make('course_links_count')->label(__('lms.fields.course_links'))->counts('courseLinks'),
                IconColumn::make('is_active')->label(__('reference.fields.is_active'))->boolean(),
                IconColumn::make('should_sync_enrollments')->label(__('lms.fields.should_sync_enrollments'))->boolean(),
                IconColumn::make('should_pull_grades')->label(__('lms.fields.should_pull_grades'))->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLmsPlatforms::route('/'),
            'create' => CreateLmsPlatform::route('/create'),
            'edit' => EditLmsPlatform::route('/{record}/edit'),
        ];
    }
}
