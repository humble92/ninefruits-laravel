<?php

namespace App\Filament\Admin\Resources\Roles\Schemas;

use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Security\Role;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.roles.sections.general'))->schema([
                    TextInput::make('name')
                        ->label(__('admin.roles.fields.name'))
                        ->helperText(__('admin.roles.fields.name_help'))
                        ->required()
                        ->regex('/^[a-z][a-z0-9_]*$/')
                        ->maxLength(100)
                        ->unique(ignoreRecord: true)
                        ->disabled(fn (?Role $record) => $record?->is_system || $record?->isPersona()),
                    TranslatableFields::make('display_name', __('admin.roles.fields.display_name')),
                    TranslatableFields::make('description', __('admin.roles.fields.description'), required: false),
                ]),
                Section::make(__('admin.roles.sections.permissions'))
                    ->description(fn (?Role $record) => $record?->is_system ? __('admin.roles.system_role_permissions') : null)
                    ->columns(2)
                    ->schema(self::permissionFields()),
            ]);
    }

    /**
     * One checkbox list per resource, stored as permissions.{resource}.
     *
     * @return list<CheckboxList>
     */
    private static function permissionFields(): array
    {
        $fields = [];

        foreach (Permission::groupedByResource() as $resource => $permissions) {
            $fields[] = CheckboxList::make("permissions.{$resource}")
                ->label(__("permissions.resources.{$resource}"))
                ->options(collect($permissions)->mapWithKeys(fn (Permission $permission) => [$permission->value => $permission->label()]))
                ->bulkToggleable()
                ->disabled(fn (?Role $record) => (bool) $record?->is_system);
        }

        return $fields;
    }

    /**
     * @param  list<string>  $permissionNames
     * @return array<string, list<string>>
     */
    public static function groupPermissions(array $permissionNames): array
    {
        $grouped = [];

        foreach (Permission::groupedByResource() as $resource => $permissions) {
            $grouped[$resource] = array_values(array_intersect(
                array_map(fn (Permission $permission) => $permission->value, $permissions),
                $permissionNames,
            ));
        }

        return $grouped;
    }

    /**
     * @param  array<string, list<string>|null>  $grouped
     * @return list<string>
     */
    public static function flattenPermissions(array $grouped): array
    {
        return array_merge(...array_values(array_map(fn (?array $names) => $names ?? [], $grouped)));
    }
}
