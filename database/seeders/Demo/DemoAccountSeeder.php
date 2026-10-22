<?php

namespace Database\Seeders\Demo;

use App\Domain\Security\Actions\AssignRole;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One fixed account per staff role template, e.g. registrar@demo.test. Two-factor
 * authentication is left unenrolled so the accounts can be used right away.
 */
class DemoAccountSeeder extends Seeder
{
    /** @var array<string, string> Role name => account name */
    public const array ACCOUNTS = [
        'system_administrator' => 'Demo Administrator',
        'registrar' => 'Demo Registrar',
        'academic_affairs' => 'Demo Academic Affairs',
        'certificate_officer' => 'Demo Certificate Officer',
        'department_assistant' => 'Demo Department Assistant',
        'admissions_officer' => 'Demo Admissions Officer',
    ];

    public function run(AssignRole $assignRole): void
    {
        $domain = config('setup.demo.email_domain');

        foreach (self::ACCOUNTS as $roleName => $name) {
            $email = str_replace('_', '-', $roleName)."@{$domain}";
            $role = Role::query()->where('name', $roleName)->first();

            if ($role === null || User::query()->where('email', $email)->exists()) {
                continue;
            }

            $user = User::factory()->create([
                'name' => $name,
                'email' => $email,
                'password' => config('setup.demo.password'),
            ]);

            $assignRole->handle($user, $role, 'Demo account');
        }
    }

    /**
     * @return list<string>
     */
    public static function emails(): array
    {
        return array_map(
            fn (string $role) => str_replace('_', '-', $role).'@'.config('setup.demo.email_domain'),
            array_keys(self::ACCOUNTS),
        );
    }
}
