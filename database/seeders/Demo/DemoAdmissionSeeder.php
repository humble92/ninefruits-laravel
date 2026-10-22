<?php

namespace Database\Seeders\Demo;

use App\Models\Admission\AdmissionCycle;
use App\Models\Admission\Application;
use App\Models\Institution\LookupValue;
use App\Models\Organization\Program;
use App\Models\Person\Person;
use App\Models\User;
use App\Support\Setup\DemoOptions;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * A regular cycle for next year and a rolling cycle, with applications in every status,
 * plus fixed portal accounts:
 *
 * - applicant@demo.test: a submitted application waiting for a decision
 * - admitted@demo.test: a released offer to accept or decline
 */
class DemoAdmissionSeeder extends Seeder
{
    /** @var array<string, string> Local part => description */
    public const array ACCOUNTS = [
        'applicant' => 'Applicant waiting for a decision',
        'admitted' => 'Admitted applicant who can accept the offer',
    ];

    public function run(DemoOptions $options): void
    {
        if (AdmissionCycle::query()->where('code', 'like', 'DEMO-%')->exists()) {
            return;
        }

        $programs = Program::query()->effectiveOn()->orderBy('code')->get();

        if ($programs->isEmpty()) {
            return;
        }

        $documents = LookupValue::query()->ofType(Application::DOCUMENT_TYPE_LOOKUP)->whereIn('code', ['diploma', 'transcript'])->get();
        $year = CarbonImmutable::today()->year + 1;

        $regular = AdmissionCycle::factory()->withPrograms($programs)->withDocuments($documents)->create([
            'code' => "DEMO-{$year}-REG",
            'name' => ['en' => "{$year} regular admission", 'ko' => "{$year}학년도 정시 모집", 'zh_CN' => "{$year}年常规招生"],
            'offer_response_days' => 14,
        ]);
        $rolling = AdmissionCycle::factory()->rolling()->withPrograms($programs->take(2))->withDocuments($documents->take(1))->create([
            'code' => 'DEMO-ROLLING',
            'name' => ['en' => 'Rolling admission', 'ko' => '상시 모집', 'zh_CN' => '常年招生'],
            'opens_at' => CarbonImmutable::now()->subYear(),
        ]);

        $states = ['submitted', 'submitted', 'admitted', 'rejected', 'confirmed', 'draft'];

        for ($index = 0; $index < $options->scale(30); $index++) {
            $cycle = $index % 5 === 0 ? $rolling : $regular;
            $program = $cycle->programs()->with('program')->get()->pluck('program')->random();
            $factory = Application::factory()->forCycle($cycle, $program);
            $factory = match ($states[$index % count($states)]) {
                'submitted' => $factory->submitted(),
                'admitted' => $factory->admitted(),
                'rejected' => $factory->rejected()->released(),
                'confirmed' => $factory->confirmed(),
                default => $factory,
            };
            $factory->create();
        }

        $domain = config('setup.demo.email_domain');
        $this->account("applicant@{$domain}", fn (Person $person) => Application::factory()->forCycle($regular, $programs->first())->submitted()->create(['person_id' => $person->id]));
        $this->account("admitted@{$domain}", fn (Person $person) => tap(
            Application::factory()->forCycle($regular, $programs->first())->admitted()->released()->create(['person_id' => $person->id]),
            fn (Application $application) => $application->forceFill(['offer_expires_at' => CarbonImmutable::now()->addDays(14)])->save(),
        ));
    }

    /**
     * @param  callable(Person): Application  $apply
     */
    private function account(string $email, callable $apply): void
    {
        $person = Person::factory()->create(['email' => $email]);
        $apply($person);

        User::factory()->create([
            'person_id' => $person->id,
            'name' => null,
            'email' => $email,
            'password' => config('setup.demo.password'),
        ]);
    }

    /**
     * @return list<string>
     */
    public static function emails(): array
    {
        return array_map(fn (string $local) => "{$local}@".config('setup.demo.email_domain'), array_keys(self::ACCOUNTS));
    }
}
