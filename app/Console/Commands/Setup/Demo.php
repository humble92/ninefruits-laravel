<?php

namespace App\Console\Commands\Setup;

use App\Console\Commands\AuditedCommand;
use App\Domain\Person\Enums\StudentStatus;
use App\Models\Person\StudentRecord;
use App\Models\User;
use App\Support\Setup\DemoOptions;
use Carbon\CarbonImmutable;
use Database\Seeders\Demo\DemoAccountSeeder;
use Database\Seeders\Demo\DemoAdmissionSeeder;
use Database\Seeders\Demo\DemoPeopleSeeder;
use Database\Seeders\Demo\DemoSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use function Laravel\Prompts\confirm;

#[Signature('setup:demo
    {--size=small : tiny (about 20 students), small (about 100), medium (about 2,000) or large (about 20,000)}
    {--seed= : Faker seed for reproducible data}
    {--force : Do not ask for confirmation (still refused in production)}
    {--student-accounts : Give every enrolled student an account (load tests) and write storage/app/private/load-accounts.csv}')]
#[Description('Create a fictional demo university from factories. Never runs in production.')]
class Demo extends AuditedCommand
{
    public function handle(): int
    {
        if ($this->laravel->isProduction()) {
            $this->components->error('Demo data cannot be created in production.');

            return self::FAILURE;
        }

        $size = (string) $this->option('size');

        if (! array_key_exists($size, (array) config('setup.demo.sizes'))) {
            $this->components->error('Size must be one of: '.implode(', ', array_keys((array) config('setup.demo.sizes'))).'.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! confirm("Create {$size} demo data in the [".config('database.connections.'.config('database.default').'.database').'] database?')) {
            return self::FAILURE;
        }

        $seed = $this->option('seed') === null ? null : (int) $this->option('seed');
        $this->laravel->instance(DemoOptions::class, new DemoOptions($size, $seed));

        $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        if ($this->option('student-accounts')) {
            $this->components->info($this->studentAccounts().' student account(s) written to storage/app/private/load-accounts.csv.');
        }

        $this->recordResult(['size' => $size, 'seed' => $seed, 'student_accounts' => (bool) $this->option('student-accounts')]);

        $this->components->info('Demo accounts (password: '.config('setup.demo.password').')');

        foreach ([...DemoAccountSeeder::emails(), ...DemoPeopleSeeder::emails(), ...DemoAdmissionSeeder::emails()] as $email) {
            $this->line("  {$email}");
        }

        return self::SUCCESS;
    }

    /**
     * Accounts for load tests (docs/manual/ko/operations/load-testing.md): one per enrolled
     * student who has none, with the demo password; the CSV feeds k6.
     */
    private function studentAccounts(): int
    {
        $domain = config('setup.demo.email_domain');
        $password = (string) config('setup.demo.password');
        // Hashing is deliberately slow; the hashed cast keeps an existing hash, so all accounts share one.
        $hash = Hash::make($password);
        $rows = ['email,password'];

        StudentRecord::query()->where('status', StudentStatus::Enrolled)->with('person.user')
            ->chunkById(1000, function (Collection $records) use ($domain, $password, $hash, &$rows): void {
                $now = CarbonImmutable::now();
                $users = [];

                foreach ($records as $record) {
                    $user = $record->person->user;
                    $email = $user === null ? "student{$record->student_number}@{$domain}" : $user->email;

                    if ($user === null) {
                        $users[] = [
                            ...User::factory()->make(['person_id' => $record->person_id, 'name' => null, 'email' => $email, 'password' => $hash])->getAttributes(),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    $rows[] = $email.','.$password;
                }

                if ($users !== []) {
                    User::query()->insert($users);
                }
            });

        Storage::disk('local')->put('load-accounts.csv', implode("\n", $rows)."\n");

        return count($rows) - 1;
    }
}
