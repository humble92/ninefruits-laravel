<?php

namespace App\Console\Commands\Setup;

use App\Console\Commands\AuditedCommand;
use App\Domain\Institution\Settings\InstitutionSettings;
use App\Models\Grading\GradeScale;
use App\Models\Security\RoleAssignment;
use App\Support\Setup\SetupProfile;
use Database\Seeders\Essential\EssentialSeeder;
use Database\Seeders\Profiles\KoreanUniversityProfileSeeder;
use DateTimeZone;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * New installation, safe to run again. Each Phase adds its steps here
 * (docs/porting-plan.md section 13.2).
 */
#[Signature('setup:install
    {--institution-name= : Institution name in the default language}
    {--locale= : Default language, e.g. ko}
    {--timezone= : IANA time zone, e.g. Asia/Seoul}
    {--profile=* : Profile keys to apply (see config/setup.php)}
    {--grade-scale= : Grade scale of the institution, 4.5 or 4.3, fixed once set (otherwise the scale of the first profile)}
    {--admin-email= : First administrator email (skipped if an administrator exists)}
    {--admin-name= : First administrator name}
    {--force : Run without questions; the administrator password is read from standard input}
')]
#[Description('Install or update the system: migrations, essential data, institution settings, first administrator, profiles')]
class Install extends AuditedCommand
{
    public function handle(InstitutionSettings $institution): int
    {
        if (! $this->checkKeys()) {
            return self::FAILURE;
        }

        $this->components->task('Migrations', fn () => $this->callSilently('migrate', [
            '--database' => config('database.migrator_connection'),
            '--force' => true,
        ]) === 0);

        $this->components->task('Essential data', fn () => $this->callSilently('db:seed', [
            '--class' => EssentialSeeder::class,
            '--force' => true,
        ]) === 0);

        $this->configureInstitution($institution);

        if (! $this->fixGradeScale()) {
            return self::FAILURE;
        }

        $profiles = $this->applyProfiles();
        $administratorCreated = $this->createAdministrator();

        $this->recordResult(['profiles' => $profiles, 'administrator_created' => $administratorCreated]);
        $this->components->info('Installation complete.');

        return self::SUCCESS;
    }

    private function checkKeys(): bool
    {
        $missing = array_keys(array_filter([
            'APP_KEY' => config('app.key'),
            'PII_ENCRYPTION_KEY' => config('pii.key'),
            'PII_HASH_KEY' => config('pii.hash_key'),
        ], fn (mixed $value) => blank($value)));

        if ($missing !== []) {
            $this->components->error('Set these environment variables first: '.implode(', ', $missing).'. See php artisan key:generate and php artisan pii:generate-key.');

            return false;
        }

        return true;
    }

    private function configureInstitution(InstitutionSettings $institution): void
    {
        $interactive = ! $this->option('force') && $this->input->isInteractive();
        $locales = $institution->supported_locales;

        $locale = $this->option('locale')
            ?: ($interactive ? select('Default language', array_combine($locales, $locales), $institution->default_locale) : $institution->default_locale);

        $timezone = $this->option('timezone')
            ?: ($interactive ? text('Time zone', default: $institution->timezone, validate: fn (string $value) => in_array($value, DateTimeZone::listIdentifiers(), true) ? null : 'Unknown time zone.') : $institution->timezone);

        $name = $this->option('institution-name')
            ?: ($interactive ? text('Institution name', default: $institution->name[$locale] ?? $institution->name['en'] ?? '') : null);

        if (! in_array($locale, $locales, true) || ! in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            $this->components->error('Unsupported language or time zone; institution settings were not changed.');

            return;
        }

        $institution->default_locale = (string) $locale;
        $institution->timezone = (string) $timezone;

        if (filled($name)) {
            $institution->name = array_merge($institution->name, [(string) $locale => (string) $name]);
        }

        $institution->save();

        $this->components->twoColumnDetail('Institution', "{$locale}, {$timezone}");
    }

    /**
     * The grade scale is the institution's scale for good: grades and GPAs are on it. It is
     * chosen here (or by the first profile) and cannot change afterwards.
     */
    private function fixGradeScale(): bool
    {
        $wanted = $this->option('grade-scale');

        if ($wanted === null || $wanted === '') {
            return true;
        }

        $current = GradeScale::query()->where('is_default', true)->first();

        if ($current !== null) {
            if ((float) $current->max_grade_points !== (float) $wanted) {
                $this->components->error("The grade scale is fixed at {$current->max_grade_points} ({$current->code}) and cannot change.");

                return false;
            }

            $this->components->info("Grade scale {$current->code} (fixed).");

            return true;
        }

        $scale = match ((string) $wanted) {
            '4.5' => KoreanUniversityProfileSeeder::seedScale45(true),
            '4.3' => KoreanUniversityProfileSeeder::seedScale43(true),
            default => null,
        };

        if ($scale === null) {
            $this->components->error('--grade-scale must be 4.5 or 4.3.');

            return false;
        }

        $this->components->info("Grade scale {$scale->code} fixed.");

        return true;
    }

    /**
     * @return list<string>
     */
    private function applyProfiles(): array
    {
        /** @var list<class-string<SetupProfile>> $available */
        $available = config('setup.profiles');
        $options = collect($available)->mapWithKeys(fn (string $class) => [$class::key() => $class::label().' — '.$class::description()])->all();

        $chosen = $this->option('profile') ?: ((! $this->option('force') && $this->input->isInteractive() && $options !== [])
            ? multiselect('Profiles to apply', $options)
            : []);

        $applied = [];

        foreach ($available as $class) {
            if (in_array($class::key(), $chosen, true)) {
                $this->components->task("Profile {$class::key()}", fn () => $this->callSilently('db:seed', ['--class' => $class, '--force' => true]) === 0);
                $applied[] = $class::key();
            }
        }

        $unknown = array_diff($chosen, array_map(fn (string $class) => $class::key(), $available));

        foreach ($unknown as $key) {
            $this->components->warn("Unknown profile [{$key}] skipped.");
        }

        return $applied;
    }

    private function createAdministrator(): bool
    {
        $hasAdministrator = RoleAssignment::query()->active()
            ->whereHas('role', fn ($query) => $query->where('name', 'system_administrator'))
            ->exists();

        if ($hasAdministrator) {
            $this->components->twoColumnDetail('Administrator', 'exists');

            return false;
        }

        $arguments = array_filter([
            '--email' => $this->option('admin-email'),
            '--name' => $this->option('admin-name'),
            '--no-interaction' => $this->option('force') ?: null,
            '--password-stdin' => $this->option('force') ?: null,
        ]);

        return $this->call('security:create-administrator', $arguments) === self::SUCCESS;
    }
}
