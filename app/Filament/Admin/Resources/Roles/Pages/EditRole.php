<?php

namespace App\Filament\Admin\Resources\Roles\Pages;

use App\Domain\Security\Actions\UpdateRolePermissions;
use App\Filament\Admin\Resources\Roles\RoleResource;
use App\Filament\Admin\Resources\Roles\Schemas\RoleForm;
use App\Models\Security\Role;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Role $role */
        $role = $this->getRecord();

        $data['display_name'] = $role->getTranslations('display_name');
        $data['description'] = $role->getTranslations('description');
        $data['permissions'] = RoleForm::groupPermissions($role->permissions()->pluck('name')->all());

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Role $record */
        // replaceTranslations: a cleared language field removes that translation
        // (assigning an array would merge with the stored translations).
        $record->replaceTranslations('display_name', array_filter($data['display_name']));
        $record->replaceTranslations('description', array_filter($data['description'] ?? []));

        if (! $record->is_system && ! $record->isPersona() && isset($data['name'])) {
            $record->name = $data['name'];
        }

        $record->save();

        if (! $record->is_system) {
            /** @var User $actor */
            $actor = Auth::user();
            app(UpdateRolePermissions::class)->handle($record, RoleForm::flattenPermissions($data['permissions'] ?? []), $actor);
        }

        return $record;
    }
}
