<?php

namespace App\Console\Commands\Security;

use App\Console\Commands\AuditedCommand;
use App\Domain\Security\Actions\UnlockUser as UnlockUserAction;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('security:unlock-user {user : Email of the user} {--reason= : Why the lock is released early (required)}')]
#[Description('Release an account locked after failed sign-ins. Requires --reason.')]
class UnlockUser extends AuditedCommand
{
    public function handle(UnlockUserAction $unlock): int
    {
        $user = User::query()->where('email', (string) $this->argument('user'))->first();
        $reason = trim((string) $this->option('reason'));

        if ($user === null) {
            $this->components->error('No user with that email.');

            return self::FAILURE;
        }

        if ($reason === '') {
            $this->components->error('--reason is required.');

            return self::FAILURE;
        }

        if (! $user->isLocked() && $user->failed_login_count === 0) {
            $this->components->info('The account is not locked.');

            return self::SUCCESS;
        }

        $unlock->handle($user, $reason, $this->actor());
        $this->recordResult(['user_id' => $user->id]);
        $this->components->info("Unlocked {$user->email}.");

        return self::SUCCESS;
    }
}
