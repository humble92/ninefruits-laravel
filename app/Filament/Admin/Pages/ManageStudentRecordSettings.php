<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Person\Settings\StudentRecordSettings;
use App\Domain\Security\Enums\Permission;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageStudentRecordSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $slug = 'settings/student-records';

    protected static function settingsClass(): string
    {
        return StudentRecordSettings::class;
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::InstitutionSettingsManage->value);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.student_records.title');
    }

    public function getTitle(): string
    {
        return __('admin.settings.student_records.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('student_number_pattern')->label(__('admin.settings.student_records.student_number_pattern'))
                    ->helperText(__('admin.settings.student_records.student_number_pattern_help'))
                    ->required()->regex('/^[^{}]*(\{(YYYY|YY)\}[^{}]*)*\{SEQ:\d{1,2}\}[^{}]*$/')->maxLength(32),
                TextInput::make('invitation_valid_days')->label(__('admin.settings.student_records.invitation_valid_days'))
                    ->integer()->minValue(1)->maxValue(90)->required(),
            ]),
        ]);
    }

    protected function settingsDataFromForm(array $state): array
    {
        return [
            'student_number_pattern' => (string) $state['student_number_pattern'],
            'invitation_valid_days' => (int) $state['invitation_valid_days'],
        ];
    }
}
