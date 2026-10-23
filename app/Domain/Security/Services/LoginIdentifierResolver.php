<?php

namespace App\Domain\Security\Services;

use App\Domain\Security\Enums\StudentNumberLogin;
use App\Domain\Security\Settings\AuthenticationSettings;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Finds the account for what was typed in the sign-in field: the personal email, or a
 * student number when the institution allows it (porting/porting-plan.md section 7.1).
 */
class LoginIdentifierResolver
{
    public function __construct(private readonly AuthenticationSettings $settings) {}

    public function resolve(string $identifier): ?User
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        if (str_contains($identifier, '@')) {
            return User::query()->where('email', Str::lower($identifier))->first();
        }

        if ($this->settings->student_number_login === StudentNumberLogin::Disabled) {
            return null;
        }

        $userId = DB::scalar('SELECT util.user_id_for_student_number(?, ?)', [
            $identifier,
            $this->settings->student_number_login === StudentNumberLogin::AllRecords,
        ]);

        return $userId === null ? null : User::query()->find($userId);
    }

    public function allowsStudentNumbers(): bool
    {
        return $this->settings->student_number_login !== StudentNumberLogin::Disabled;
    }
}
