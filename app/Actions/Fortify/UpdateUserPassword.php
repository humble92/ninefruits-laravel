<?php

namespace App\Actions\Fortify;

use App\Domain\Security\Actions\RecordSecurityEvent;
use App\Domain\Security\Enums\SecurityEventType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and update the user's password.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ])->validateWithBag('updatePassword');

        $user->forceFill([
            'password' => Hash::make($input['password']),
            'remember_token' => Str::random(60),
        ])->save();

        // Other sessions and remember-me cookies stop working (security review M7).
        if (Auth::guard('web')->id() === $user->id) {
            Auth::logoutOtherDevices($input['password']);
        }

        app(RecordSecurityEvent::class)->handle(SecurityEventType::PasswordChanged, $user);
    }
}
