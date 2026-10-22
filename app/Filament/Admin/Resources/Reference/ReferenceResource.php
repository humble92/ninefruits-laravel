<?php

namespace App\Filament\Admin\Resources\Reference;

use App\Filament\Admin\NavigationGroup;
use Filament\Resources\Resource;
use UnitEnum;

/**
 * Base for reference data resources: navigation group and translated labels
 * (reference.resources.{key}). Authorization comes from the model policies.
 */
abstract class ReferenceResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::ReferenceData;

    abstract protected static function labelKey(): string;

    public static function getModelLabel(): string
    {
        return __('reference.resources.'.static::labelKey().'.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('reference.resources.'.static::labelKey().'.plural_label');
    }
}
