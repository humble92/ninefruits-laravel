<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * New accounts get a random password and a password reset link by email.
 */
class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['password'] = Str::password(32);

        return $data;
    }

    protected function afterCreate(): void
    {
        Password::broker()->sendResetLink(['email' => $this->getRecord()->getAttribute('email')]);
    }
}
