<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Domain\Security\Actions\ResetUserMfa;
use App\Domain\Security\Actions\UnlockUser;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('resetMfa')
                ->label(__('admin.users.actions.reset_mfa'))
                ->icon(Heroicon::OutlinedKey)
                ->color('danger')
                ->visible(fn (User $record) => $record->two_factor_confirmed_at !== null || $record->email_mfa_enabled_at !== null)
                ->authorize(fn (User $record) => Auth::user()?->can('resetMfa', $record) ?? false)
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000),
                ])
                ->action(function (User $record, array $data, ResetUserMfa $reset) {
                    /** @var User $actor */
                    $actor = Auth::user();
                    $reset->handle($record, $data['reason'], $actor);

                    Notification::make()->success()->title(__('admin.users.notifications.mfa_reset'))->send();
                }),
            Action::make('unlock')
                ->label(__('admin.users.actions.unlock'))
                ->icon(Heroicon::OutlinedLockOpen)
                ->visible(fn (User $record) => $record->isLocked())
                ->authorize(fn (User $record) => Auth::user()?->can('unlock', $record) ?? false)
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000),
                ])
                ->action(function (User $record, array $data, UnlockUser $unlock) {
                    /** @var User $actor */
                    $actor = Auth::user();
                    $unlock->handle($record, $data['reason'], $actor);

                    Notification::make()->success()->title(__('admin.users.notifications.unlocked'))->send();
                }),
        ];
    }
}
