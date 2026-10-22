<?php

namespace App\Filament\Admin\Imports;

use App\Domain\Security\Enums\Permission;
use Filament\Actions\ImportAction;
use Illuminate\Support\Facades\Auth;

/**
 * Import types for the admin screens and `php artisan reference:import {type}`.
 * Students and instructors (Phase 2) and courses (Phase 4) are added with their tables.
 */
final class ReferenceImporters
{
    /** @var array<string, class-string<ReferenceImporter>> */
    public const array TYPES = [
        'org_units' => OrgUnitImporter::class,
        'programs' => ProgramImporter::class,
        'buildings' => BuildingImporter::class,
        'rooms' => RoomImporter::class,
        'lookup_values' => LookupValueImporter::class,
    ];

    public static function action(string $type): ImportAction
    {
        return ImportAction::make("import_{$type}")
            ->label(__('reference.actions.import_csv'))
            ->importer(self::TYPES[$type])
            ->authorize(fn () => (bool) Auth::user()?->can(Permission::ReferenceDataImport->value));
    }
}
