<?php

namespace App\Filament\Admin\Resources\Certificate\Types\RelationManagers;

use App\Domain\Certificate\Services\CertificateRenderer;
use App\Domain\Institution\Settings\InstitutionSettings;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * The wording of a kind per language. Placeholders in braces are filled at issue time.
 */
class TemplatesRelationManager extends RelationManager
{
    protected static string $relationship = 'templates';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('certificate.fields.templates');
    }

    public function isReadOnly(): bool
    {
        return ! Gate::allows('update', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        $placeholders = implode(', ', array_map(fn (string $name) => '{'.$name.'}', CertificateRenderer::PLACEHOLDERS));

        return $schema->columns(1)->components([
            Select::make('locale')->label(__('common.language'))->required()
                ->options(fn () => collect(app(InstitutionSettings::class)->supported_locales)->mapWithKeys(fn (string $locale) => [$locale => $locale]))
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('certificate_type_id', $this->getOwnerRecord()->getKey())),
            TextInput::make('title')->label(__('certificate.fields.title'))->required()->maxLength(160),
            Textarea::make('body')->label(__('certificate.fields.body'))->required()->rows(6)
                ->helperText(__('certificate.help.body', ['placeholders' => $placeholders])),
            Textarea::make('footer')->label(__('certificate.fields.footer'))->rows(2)->maxLength(1000),
            TextInput::make('signer')->label(__('certificate.fields.signer'))->maxLength(160)->helperText(__('certificate.help.signer')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('locale')->label(__('common.language'))->badge()->color('gray'),
                TextColumn::make('title')->label(__('certificate.fields.title')),
                TextColumn::make('signer')->label(__('certificate.fields.signer'))->placeholder('-'),
            ])
            ->defaultSort('locale')
            ->paginated(false)
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
