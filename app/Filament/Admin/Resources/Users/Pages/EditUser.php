<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Domain\Security\Actions\UpdateUserAccount;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        /** @var User $actor */
        $actor = Auth::user();

        return app(UpdateUserAccount::class)->handle($record, $data, $actor);
    }
}
