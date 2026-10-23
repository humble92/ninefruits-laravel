<?php

namespace App\Filament\Admin\Resources\Certificate\Types;

use App\Domain\Certificate\Enums\CertificateAudience;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Certificate\Types\Pages\CreateCertificateType;
use App\Filament\Admin\Resources\Certificate\Types\Pages\EditCertificateType;
use App\Filament\Admin\Resources\Certificate\Types\Pages\ListCertificateTypes;
use App\Filament\Admin\Resources\Certificate\Types\RelationManagers\TemplatesRelationManager;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Certificate\CertificateType;
use BackedEnum;
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
 * Certificate kinds (legacy FCERT with one PDFlib script per kind and language): the
 * wording per language is a template the institution edits.
 */
class CertificateTypeResource extends Resource
{
    protected static ?string $model = CertificateType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Certificates;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'certificates/types';

    public static function getModelLabel(): string
    {
        return __('certificate.resources.types.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('certificate.resources.types.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('code')->label(__('reference.fields.code'))->required()->maxLength(32)->alphaDash()->unique(ignoreRecord: true)
                ->helperText(__('certificate.help.code')),
            Select::make('audience')->label(__('certificate.fields.audience'))->required()->default(CertificateAudience::Any->value)
                ->options(collect(CertificateAudience::cases())->mapWithKeys(fn (CertificateAudience $case) => [$case->value => $case->label()])),
            TranslatableFields::make('name', __('reference.fields.name'))->columnSpanFull(),
            Toggle::make('has_grades')->label(__('certificate.fields.has_grades'))->helperText(__('certificate.help.has_grades')),
            Toggle::make('has_photo')->label(__('certificate.fields.has_photo')),
            Toggle::make('is_active')->label(__('reference.fields.is_active'))->default(true),
            TextInput::make('sort_order')->label(__('reference.fields.sort_order'))->integer()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable(),
                TextColumn::make('name')->label(__('reference.fields.name')),
                TextColumn::make('audience')->label(__('certificate.fields.audience'))->formatStateUsing(fn (CertificateAudience $state) => $state->label()),
                TextColumn::make('templates_count')->label(__('certificate.fields.templates'))->counts('templates'),
                IconColumn::make('has_grades')->label(__('certificate.fields.has_grades'))->boolean(),
                IconColumn::make('is_active')->label(__('reference.fields.is_active'))->boolean(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [TemplatesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCertificateTypes::route('/'),
            'create' => CreateCertificateType::route('/create'),
            'edit' => EditCertificateType::route('/{record}/edit'),
        ];
    }
}
