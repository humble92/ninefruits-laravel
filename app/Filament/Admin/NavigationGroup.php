<?php

namespace App\Filament\Admin;

use Filament\Navigation\NavigationGroup as FilamentNavigationGroup;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

/**
 * Admin panel navigation groups, in display order.
 */
enum NavigationGroup implements HasLabel
{
    case StudentRecords;
    case Admissions;
    case Curriculum;
    case Registration;
    case Grading;
    case ReferenceData;
    case Access;
    case Audit;
    case Settings;

    public function getLabel(): string
    {
        return __('admin.navigation.'.Str::snake($this->name));
    }

    /**
     * Groups keyed by case name, as Filament keys enum groups. The label is a closure so it
     * is translated when the menu renders, after the locale middleware has run (panel
     * configuration happens earlier, at boot).
     *
     * @return array<string, FilamentNavigationGroup>
     */
    public static function forPanel(): array
    {
        $groups = [];

        foreach (self::cases() as $case) {
            $groups[$case->name] = FilamentNavigationGroup::make()->label(fn () => $case->getLabel());
        }

        return $groups;
    }
}
