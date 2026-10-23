<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Grading\Enums\GpaRounding;
use App\Domain\Grading\Enums\RetakePolicy;
use App\Domain\Grading\Settings\GradingSettings;
use App\Domain\Security\Enums\Permission;
use App\Models\Grading\GradeScale;
use App\Models\Grading\GradeScaleItem;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Grading policy: GPA precision, retakes, academic standing and honors. Changes apply to
 * calculations from now on; recalculate term records to apply them to past terms.
 */
class ManageGradingSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?string $slug = 'settings/grading';

    protected static function settingsClass(): string
    {
        return GradingSettings::class;
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::GradingPoliciesManage->value);
    }

    public static function getNavigationLabel(): string
    {
        return __('grading.settings.title');
    }

    public function getTitle(): string
    {
        return __('grading.settings.title');
    }

    public function form(Schema $schema): Schema
    {
        $grades = fn () => GradeScaleItem::query()->whereIn('grade_scale_id', GradeScale::query()->where('is_default', true)->select('id'))
            ->whereNotNull('grade_points')->orderBy('sort_order')->pluck('code', 'code');

        return $schema->components([
            Section::make(__('grading.settings.gpa'))->columns(2)->schema([
                TextInput::make('gpa_decimals')->label(__('grading.settings.gpa_decimals'))->integer()->minValue(0)->maxValue(3)->required(),
                Select::make('gpa_rounding')->label(__('grading.settings.gpa_rounding'))->required()
                    ->options(collect(GpaRounding::cases())->mapWithKeys(fn (GpaRounding $case) => [$case->value => $case->label()])),
                Select::make('retake_policy')->label(__('grading.settings.retake_policy'))->required()->helperText(__('grading.settings.retake_policy_help'))
                    ->options(collect(RetakePolicy::cases())->mapWithKeys(fn (RetakePolicy $case) => [$case->value => $case->label()])),
                Select::make('retake_max_grade')->label(__('grading.settings.retake_max_grade'))->options($grades)
                    ->placeholder(__('grading.settings.no_cap'))->helperText(__('grading.settings.retake_max_grade_help')),
            ]),
            Section::make(__('grading.settings.standing'))->columns(2)->schema([
                TextInput::make('warning_gpa_below')->label(__('grading.settings.warning_gpa_below'))->numeric()->minValue(0)->maxValue(5)->required()
                    ->helperText(__('grading.settings.warning_gpa_below_help')),
                Toggle::make('standing_regular_terms_only')->label(__('grading.settings.standing_regular_terms_only')),
                TextInput::make('dismissal_warning_count')->label(__('grading.settings.dismissal_warning_count'))->integer()->minValue(0)->maxValue(10)->required()
                    ->helperText(__('grading.settings.dismissal_warning_count_help')),
                Toggle::make('dismissal_consecutive_only')->label(__('grading.settings.dismissal_consecutive_only')),
            ]),
            Section::make(__('grading.settings.honors'))->columns(2)->schema([
                TextInput::make('honors_min_gpa')->label(__('grading.settings.honors_min_gpa'))->numeric()->minValue(0)->maxValue(5)->required(),
                TextInput::make('honors_min_credits')->label(__('grading.settings.honors_min_credits'))->numeric()->minValue(0)->maxValue(40)->required(),
                Toggle::make('honors_exclude_failing')->label(__('grading.settings.honors_exclude_failing')),
            ]),
        ]);
    }

    protected function settingsDataFromForm(array $state): array
    {
        return [
            'gpa_decimals' => (int) $state['gpa_decimals'],
            'gpa_rounding' => (string) $state['gpa_rounding'],
            'retake_policy' => (string) $state['retake_policy'],
            'retake_max_grade' => filled($state['retake_max_grade'] ?? null) ? (string) $state['retake_max_grade'] : null,
            'standing_regular_terms_only' => (bool) $state['standing_regular_terms_only'],
            'warning_gpa_below' => (float) $state['warning_gpa_below'],
            'dismissal_warning_count' => (int) $state['dismissal_warning_count'],
            'dismissal_consecutive_only' => (bool) $state['dismissal_consecutive_only'],
            'honors_min_gpa' => (float) $state['honors_min_gpa'],
            'honors_min_credits' => (float) $state['honors_min_credits'],
            'honors_exclude_failing' => (bool) $state['honors_exclude_failing'],
        ];
    }
}
