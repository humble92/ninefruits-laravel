<?php

namespace Database\Seeders\Demo;

use App\Domain\Person\Enums\AdvisorRole;
use App\Domain\Person\Enums\EmploymentType;
use App\Domain\Person\Enums\GuardianRelationship;
use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Person\Services\StudentNumberGenerator;
use App\Models\Organization\Program;
use App\Models\Person\AdvisorAssignment;
use App\Models\Person\Guardian;
use App\Models\Person\Instructor;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\Person\StudentStatusChange;
use App\Models\Person\StudentStatusChangeType;
use App\Models\User;
use App\Support\Setup\DemoOptions;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Instructors, students across admission years and statuses, alumni, guardians, advisors
 * and pending status change requests, plus fixed portal accounts:
 *
 * - student@demo.test: a current student with an advisor
 * - alumni@demo.test: a graduate
 * - readmitted@demo.test: a graduate admitted again (two student records)
 * - instructor@demo.test: an instructor who advises students
 */
class DemoPeopleSeeder extends Seeder
{
    /** @var array<string, string> Local part => description */
    public const array ACCOUNTS = [
        'student' => 'Current student',
        'alumni' => 'Graduate',
        'readmitted' => 'Graduate admitted again (two records)',
        'instructor' => 'Instructor and advisor',
    ];

    public function __construct(private readonly StudentNumberGenerator $numbers) {}

    public function run(DemoOptions $options): void
    {
        if (Instructor::query()->where('employee_number', 'like', 'DEMO-%')->exists()) {
            return;
        }

        /** @var Collection<int, Program> $programs */
        $programs = Program::query()->effectiveOn()->whereHas('orgUnit', fn ($unit) => $unit->effectiveOn())->orderBy('code')->get();

        if ($programs->isEmpty()) {
            return;
        }

        $instructors = $this->instructors($programs, $options);
        $this->students($programs, $instructors, $options);
        $this->fixedAccounts($programs, $instructors);
    }

    /**
     * @param  Collection<int, Program>  $programs
     * @return Collection<int, Collection<int, Instructor>> Instructors by org unit id
     */
    private function instructors(Collection $programs, DemoOptions $options): Collection
    {
        $perUnit = max(2, intdiv($options->scale(20), $programs->count()));
        $sequence = 0;

        $create = function (int $orgUnitId) use (&$sequence): Instructor {
            return Instructor::factory()->create([
                'employee_number' => sprintf('DEMO-%05d', ++$sequence),
                'org_unit_id' => $orgUnitId,
                'employment_type' => fake()->randomElement([EmploymentType::FullTime, EmploymentType::FullTime, EmploymentType::PartTime, EmploymentType::Adjunct]),
            ]);
        };

        return $programs->pluck('org_unit_id')->unique()->mapWithKeys(fn (int $orgUnitId) => [
            $orgUnitId => Collection::times($perUnit, fn () => $create($orgUnitId)),
        ]);
    }

    /**
     * @param  Collection<int, Program>  $programs
     * @param  Collection<int, Collection<int, Instructor>>  $instructors
     */
    private function students(Collection $programs, Collection $instructors, DemoOptions $options): void
    {
        $thisYear = CarbonImmutable::today()->year;
        $leave = StudentStatusChangeType::query()->where('code', 'leave_of_absence')->first();

        for ($index = 0; $index < $options->studentCount(); $index++) {
            $program = $programs[$index % $programs->count()];
            // Admission years spread over six years; the two oldest are mostly graduates.
            $year = $thisYear - ($index % 6);
            $status = match (true) {
                $year <= $thisYear - 4 => fake()->randomElement([StudentStatus::Graduated, StudentStatus::Graduated, StudentStatus::Graduated, StudentStatus::Withdrawn]),
                default => fake()->randomElement([...array_fill(0, 12, StudentStatus::Enrolled), StudentStatus::OnLeave, StudentStatus::Withdrawn]),
            };

            $record = $this->record(Person::factory()->create(), $program, $year, $status);

            if ($index % 3 === 0) {
                Guardian::query()->create([
                    'person_id' => $record->person_id,
                    'relationship' => fake()->randomElement([GuardianRelationship::Mother, GuardianRelationship::Father]),
                    'full_name' => fake()->name(),
                    'phone' => fake()->numerify('010-####-####'),
                    'is_primary_contact' => true,
                ]);
            }

            if ($status->isActive()) {
                $this->assignAdvisor($record, $instructors[$program->org_unit_id]->random());
            }

            if ($leave !== null && $status === StudentStatus::Enrolled && $index % 25 === 0) {
                StudentStatusChange::query()->create([
                    'student_record_id' => $record->id,
                    'status_change_type_id' => $leave->id,
                    'from_status' => StudentStatus::Enrolled,
                    'to_status' => StudentStatus::OnLeave,
                    'effective_date' => CarbonImmutable::today()->addWeeks(2)->toDateString(),
                    'reason' => 'Military service',
                    'requested_at' => CarbonImmutable::now(),
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, Program>  $programs
     * @param  Collection<int, Collection<int, Instructor>>  $instructors
     */
    private function fixedAccounts(Collection $programs, Collection $instructors): void
    {
        $domain = config('setup.demo.email_domain');
        $thisYear = CarbonImmutable::today()->year;
        $program = $programs->first();
        $advisor = $instructors[$program->org_unit_id]->first();

        $this->account($advisor->person, "instructor@{$domain}");

        $student = Person::factory()->create(['email' => "student@{$domain}"]);
        $this->assignAdvisor($this->record($student, $program, $thisYear - 1, StudentStatus::Enrolled), $advisor);
        $this->account($student, "student@{$domain}");

        $graduate = Person::factory()->create(['email' => "alumni@{$domain}"]);
        $this->record($graduate, $program, $thisYear - 5, StudentStatus::Graduated);
        $this->account($graduate, "alumni@{$domain}");

        $readmitted = Person::factory()->create(['email' => "readmitted@{$domain}"]);
        $this->record($readmitted, $program, $thisYear - 6, StudentStatus::Graduated);
        $this->assignAdvisor($this->record($readmitted, $programs->last(), $thisYear, StudentStatus::Enrolled), $instructors[$programs->last()->org_unit_id]->first());
        $this->account($readmitted, "readmitted@{$domain}");
    }

    private function record(Person $person, Program $program, int $year, StudentStatus $status): StudentRecord
    {
        $admission = CarbonImmutable::create($year, 3, 1);

        $record = new StudentRecord;
        $record->forceFill([
            'person_id' => $person->id,
            'student_number' => $this->numbers->next($year),
            'program_id' => $program->id,
            'org_unit_id' => $program->org_unit_id,
            'admission_date' => $admission,
            'catalog_year' => $year,
            'year_level' => min(4, max(1, CarbonImmutable::today()->year - $year + 1)),
            'status' => $status,
            'status_changed_at' => CarbonImmutable::now(),
            'graduation_date' => $status === StudentStatus::Graduated ? $admission->addYears(4)->subDays(2) : null,
        ])->save();

        return $record;
    }

    private function assignAdvisor(StudentRecord $record, Instructor $instructor): void
    {
        AdvisorAssignment::query()->create([
            'student_record_id' => $record->id,
            'instructor_id' => $instructor->id,
            'role' => AdvisorRole::Primary,
            'start_date' => $record->admission_date,
        ]);
    }

    private function account(Person $person, string $email): void
    {
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
