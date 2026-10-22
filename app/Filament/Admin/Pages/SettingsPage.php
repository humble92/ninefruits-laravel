<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Security\Services\AuditLogger;
use App\Filament\Admin\NavigationGroup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Settings;
use UnitEnum;

/**
 * @property-read Schema $form
 *
 * Edits a spatie/laravel-settings class. Changes are audited (business audit here, data
 * audit by the trigger on the settings table).
 */
abstract class SettingsPage extends Page
{
    /** @var array<string, mixed>|null */
    public ?array $data = [];

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Settings;

    /**
     * @return class-string<Settings>
     */
    abstract protected static function settingsClass(): string;

    public function mount(): void
    {
        $this->form->fill($this->formDataFromSettings($this->settings()));
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label(__('common.save'))->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(AuditLogger $audit): void
    {
        abort_unless(static::canAccess(), 403);

        $settings = $this->settings();
        $before = $settings->toArray();
        $state = $this->form->getState();

        DB::transaction(function () use ($settings, $state, $before, $audit) {
            $settings->fill($this->settingsDataFromForm($state))->save();

            $audit->record('settings.updated', null, properties: [
                'group' => $settings::group(),
                'before' => $before,
                'after' => $settings->toArray(),
            ], log: 'security');
        });

        Notification::make()->success()->title(__('common.saved'))->send();
    }

    protected function settings(): Settings
    {
        return app(static::settingsClass());
    }

    /**
     * @return array<string, mixed>
     */
    protected function formDataFromSettings(Settings $settings): array
    {
        return array_map(
            fn (mixed $value) => $value instanceof BackedEnum ? $value->value : $value,
            $settings->toArray(),
        );
    }

    /**
     * Converts form state to the typed settings properties.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    abstract protected function settingsDataFromForm(array $state): array;
}
