<?php

namespace App\Console\Commands\Security;

use App\Console\Commands\AuditedCommand;
use App\Domain\Security\Actions\ResetUserMfa;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

/**
 * For when no administrator can sign in to the admin panel (e.g. the only administrator lost
 * their phone). Otherwise use the reset action on the user screen.
 */
#[Signature('security:reset-mfa {user : Email of the user} {--reason= : Why the reset is needed (required)}')]
#[Description('Reset a user\'s two-factor authentication. Requires --reason and --actor.')]
class ResetMfa extends AuditedCommand
{
    public function handle(ResetUserMfa $reset): int
    {
        $user = User::query()->where('email', (string) $this->argument('user'))->first();
        $reason = trim((string) $this->option('reason'));
        $actor = $this->actor();

        if ($user === null) {
            $this->components->error('No user with that email.');

            return self::FAILURE;
        }

        if ($reason === '' || $actor === null) {
            $this->components->error('--reason and --actor are required.');

            return self::FAILURE;
        }

        $reset->handle($user, $reason, $actor);
        $this->recordResult(['user_id' => $user->id]);
        $this->components->info("Two-factor authentication reset for {$user->email}. They enroll again at the next sign-in.");

        return self::SUCCESS;
    }
}
