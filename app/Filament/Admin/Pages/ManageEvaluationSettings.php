<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Evaluation\Settings\EvaluationSettings;
use App\Domain\Security\Enums\Permission;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageEvaluationSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $slug = 'settings/evaluation';

    protected static function settingsClass(): string
    {
        return EvaluationSettings::class;
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::EvaluationsManage->value);
    }

    public static function getNavigationLabel(): string
    {
        return __('evaluation.settings.title');
    }

    public function getTitle(): string
    {
        return __('evaluation.settings.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Toggle::make('grades_require_evaluation')->label(__('evaluation.settings.grades_require_evaluation'))
                    ->helperText(__('evaluation.settings.grades_require_evaluation_help'))->columnSpanFull(),
                Toggle::make('reject_uniform_answers')->label(__('evaluation.settings.reject_uniform_answers'))
                    ->helperText(__('evaluation.settings.reject_uniform_answers_help'))->columnSpanFull(),
                TextInput::make('minimum_responses')->label(__('evaluation.fields.minimum_responses'))->integer()->minValue(0)->maxValue(100)->required()
                    ->helperText(__('evaluation.settings.minimum_help')),
                TextInput::make('minimum_response_percent')->label(__('evaluation.fields.minimum_response_percent'))->integer()->minValue(0)->maxValue(100)->required(),
            ]),
        ]);
    }

    protected function settingsDataFromForm(array $state): array
    {
        return [
            'grades_require_evaluation' => (bool) $state['grades_require_evaluation'],
            'reject_uniform_answers' => (bool) $state['reject_uniform_answers'],
            'minimum_responses' => (int) $state['minimum_responses'],
            'minimum_response_percent' => (int) $state['minimum_response_percent'],
        ];
    }
}
