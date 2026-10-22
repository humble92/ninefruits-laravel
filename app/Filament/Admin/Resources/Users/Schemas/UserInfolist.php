<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Domain\Security\Services\MfaPolicy;
use App\Models\User;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')->label(__('admin.users.fields.name')),
                TextEntry::make('email')->label(__('admin.users.fields.email')),
                TextEntry::make('locale')->label(__('admin.users.fields.locale'))->placeholder('-'),
                IconEntry::make('is_active')->label(__('admin.users.fields.is_active'))->boolean(),
                IconEntry::make('mfa_enrolled')
                    ->label(__('admin.users.fields.mfa_enrolled'))
                    ->state(fn (User $record) => app(MfaPolicy::class)->isEnrolled($record))
                    ->boolean(),
                TextEntry::make('last_login_at')->label(__('admin.users.fields.last_login_at'))->dateTime()->placeholder('-'),
            ]);
    }
}
