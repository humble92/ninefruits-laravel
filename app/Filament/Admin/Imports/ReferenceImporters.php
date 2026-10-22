<?php

namespace App\Filament\Admin\Imports;

use App\Domain\Security\Enums\Permission;
use Filament\Actions\ImportAction;
use Illuminate\Support\Facades\Auth;

/**
 * Import types for the admin screens and `php artisan reference:import {type}`.
 * Courses (Phase 4) are added with their table.
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
        'students' => StudentImporter::class,
        'instructors' => InstructorImporter::class,
    ];

    /** Types whose screens need a permission other than reference_data.import. */
    private const array PERMISSIONS = [
        'students' => Permission::StudentRecordsManage,
        'instructors' => Permission::InstructorsManage,
    ];

    public static function action(string $type): ImportAction
    {
        return ImportAction::make("import_{$type}")
            ->label(__('reference.actions.import_csv'))
            ->importer(self::TYPES[$type])
            ->authorize(fn () => (bool) Auth::user()?->can((self::PERMISSIONS[$type] ?? Permission::ReferenceDataImport)->value));
    }
}
