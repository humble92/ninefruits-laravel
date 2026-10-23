<?php

namespace App\Console\Commands\Security;

use App\Console\Commands\AuditedCommand;
use App\Domain\Security\Actions\AssignRole;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('security:create-administrator {--email=} {--name=} {--password-stdin : Read the password from standard input (non-interactive installs)}')]
#[Description('Create a user with the System Administrator role (initial installation)')]
class CreateAdministrator extends AuditedCommand
{
    public function handle(AssignRole $assignRole): int
    {
        $role = Role::query()->where('name', 'system_administrator')->first();

        if ($role === null) {
            $this->components->error('The system_administrator role is missing. Run php artisan setup:install or php artisan db:seed first.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: text('Name', required: true);
        $email = $this->option('email') ?: text('Email', required: true);
        if ($this->option('password-stdin')) {
            $secret = trim((string) fgets(STDIN));
        } elseif ($this->input->isInteractive()) {
            $secret = password('Password', required: true);
        } else {
            $this->components->error('Run interactively or pass --password-stdin.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            ['email' => $email, 'password' => $secret],
            ['email' => ['email', 'unique:users,email'], 'password' => [Password::defaults()]],
        );

        if ($validator->fails()) {
            $this->components->error($validator->errors()->first());

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($name, $email, $secret, $role, $assignRole) {
            $user = User::query()->create(['name' => $name, 'email' => $email, 'password' => $secret]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $assignRole->handle($user, $role, 'Initial administrator created by security:create-administrator', assignedBy: $this->actor(), enforceActorLimits: false);

            return $user;
        });

        $this->recordResult(['user_id' => $user->id, 'email' => $email]);

        $this->components->info("Administrator {$email} created.");

        return self::SUCCESS;
    }
}
