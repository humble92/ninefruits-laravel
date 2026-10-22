<?php

namespace App\Filament\Admin\Support;

use Closure;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Validation\ValidationException;

/**
 * Domain actions report problems as ValidationException with their own field names
 * (e.g. a room conflict on room_id). Filament only shows errors under its form path, so
 * this shows the message as a notification and keeps the modal open.
 */
final class ShowsActionErrors
{
    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public static function run(Closure $callback): mixed
    {
        try {
            return $callback();
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title((string) collect($exception->errors())->flatten()->first())->send();

            // As Action::halt(): stop and keep the modal open.
            throw new Halt;
        }
    }
}
