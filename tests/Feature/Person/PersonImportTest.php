<?php

use App\Domain\Person\Enums\EmploymentType;
use App\Domain\Person\Enums\StudentStatus;
use App\Models\Organization\Program;
use App\Models\Person\Instructor;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Database\Seeders\Essential\EssentialSeeder;
use Illuminate\Testing\PendingCommand;

beforeEach(function () {
    $this->seed(EssentialSeeder::class);
    User::factory()->create(['email' => 'ops@example.com']);
    $this->program = Program::factory()->create(['code' => 'B-CSE', 'effective_start_date' => '2000-01-01']);
    $this->directory = sys_get_temp_dir().'/people-import-'.uniqid();
    mkdir($this->directory);
});

afterEach(function () {
    array_map('unlink', glob("{$this->directory}/*"));
    rmdir($this->directory);
});

function peopleCsv(string $directory, array $rows): string
{
    $path = "{$directory}/".uniqid().'.csv';
    $handle = fopen($path, 'w');

    foreach ($rows as $row) {
        fputcsv($handle, $row, escape: '');
    }

    fclose($handle);

    return $path;
}

function importPeople(string $type, string $file): PendingCommand
{
    return test()->artisan('reference:import', ['type' => $type, 'file' => $file, '--actor' => 'ops@example.com']);
}

it('imports students with people, and updates them by student number', function () {
    $file = peopleCsv($this->directory, [
        ['student_number', 'program', 'admission_date', 'year_level', 'status', 'family_name', 'given_name', 'name_ko', 'birth_date', 'national_id'],
        ['201900001', 'B-CSE', '2019-03-01', '4', 'enrolled', 'Kim', 'Minsu', '김민수', '2000-05-01', 'TEST-0000000001'],
        ['201900002', 'B-CSE', '2019-03-01', '3', 'on_leave', 'Lee', 'Jiwoo', '이지우', '2000-07-01', ''],
        ['201900003', 'NOPE', '2019-03-01', '1', 'enrolled', 'Park', 'Bad', '', '', ''],
    ]);

    importPeople('students', $file)->assertExitCode(2);

    $record = StudentRecord::query()->where('student_number', '201900001')->sole();
    expect($record->program_id)->toBe($this->program->id)
        ->and($record->org_unit_id)->toBe($this->program->org_unit_id)
        ->and($record->catalog_year)->toBe(2019)
        ->and($record->person->name_variants)->toBe(['ko' => '김민수'])
        ->and($record->person->national_id_encrypted)->toBe('TEST-0000000001')
        ->and(StudentRecord::query()->where('student_number', '201900002')->sole()->status)->toBe(StudentStatus::OnLeave)
        ->and(StudentRecord::query()->where('student_number', '201900003')->exists())->toBeFalse();

    $update = peopleCsv($this->directory, [
        ['student_number', 'year_level', 'phone'],
        ['201900001', '4', '010-0000-1111'],
    ]);

    importPeople('students', $update)->assertSuccessful();

    expect(StudentRecord::query()->count())->toBe(2)
        ->and($record->person->fresh()->phone)->toBe('010-0000-1111')
        ->and($record->person->fresh()->family_name)->toBe('Kim');
});

it('attaches a readmission to the existing person by national id', function () {
    $person = Person::factory()->withNationalId('TEST-0000000009')->create();
    StudentRecord::factory()->inProgram($this->program)->graduated()->create(['person_id' => $person->id, 'student_number' => '201500009']);

    importPeople('students', peopleCsv($this->directory, [
        ['student_number', 'program', 'admission_date', 'family_name', 'national_id'],
        ['202600009', 'B-CSE', '2026-03-01', 'Ignored', 'TEST-0000000009'],
    ]))->assertSuccessful();

    expect(StudentRecord::query()->where('student_number', '202600009')->sole()->person_id)->toBe($person->id)
        ->and(Person::query()->count())->toBe(1);
});

it('imports instructors with translated titles', function () {
    importPeople('instructors', peopleCsv($this->directory, [
        ['employee_number', 'org_unit', 'employment_type', 'academic_title_en', 'academic_title_ko', 'family_name', 'given_name'],
        ['F00001', $this->program->orgUnit->code, 'full_time', 'Professor', '교수', 'Choi', 'Yuna'],
    ]))->assertSuccessful();

    $instructor = Instructor::query()->where('employee_number', 'F00001')->sole();

    expect($instructor->employment_type)->toBe(EmploymentType::FullTime)
        ->and($instructor->getTranslations('academic_title'))->toBe(['en' => 'Professor', 'ko' => '교수'])
        ->and($instructor->person->family_name)->toBe('Choi');
});
