<?php

namespace App\Filament\Admin\Support;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/**
 * Delete that explains when other records still use the row (foreign key restriction),
 * instead of failing with a server error. Reference data in use should be deactivated
 * or ended instead.
 */
final class SafeDeleteAction
{
    public static function make(): DeleteAction
    {
        return DeleteAction::make()->using(function (Model $record): bool {
            try {
                return (bool) $record->getConnection()->transaction(fn () => $record->delete());
            } catch (QueryException $exception) {
                // 23503 foreign_key_violation (NO ACTION), 23001 restrict_violation (RESTRICT).
                if (! in_array($exception->getCode(), ['23503', '23001'], true)) {
                    throw $exception;
                }

                Notification::make()->danger()
                    ->title(__('reference.errors.in_use_title'))
                    ->body(__('reference.errors.in_use_body'))
                    ->send();

                throw new Halt;
            }
        });
    }
}
