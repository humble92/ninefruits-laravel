<?php

use App\Models\Facility\Building;
use App\Models\Facility\Room;
use App\Models\Institution\LookupValue;
use App\Models\Organization\OrgUnit;
use App\Models\User;
use Database\Seeders\Essential\EssentialSeeder;
use Database\Seeders\Profiles\KoreanUniversityProfileSeeder;

beforeEach(function () {
    $this->seed([EssentialSeeder::class, KoreanUniversityProfileSeeder::class]);
    $this->operator = User::factory()->create(['email' => 'ops@example.com']);
    $this->directory = sys_get_temp_dir().'/import-'.uniqid();
    mkdir($this->directory);
});

afterEach(function () {
    array_map('unlink', glob("{$this->directory}/*"));
    rmdir($this->directory);
});

function csvFile(string $directory, string $name, array $rows): string
{
    $path = "{$directory}/{$name}";
    $handle = fopen($path, 'w');

    foreach ($rows as $row) {
        fputcsv($handle, $row, escape: '');
    }

    fclose($handle);

    return $path;
}

it('imports org units with parents and translations, and updates on a second import', function () {
    $file = csvFile($this->directory, 'org.csv', [
        ['code', 'type', 'parent', 'name_en', 'name_ko', 'effective_start_date'],
        ['UNI', 'university', '', 'Sample University', '샘플대학교', '1990-03-01'],
        ['ENG', 'college', 'UNI', 'College of Engineering', '공과대학', '1990-03-01'],
        ['CSE', 'department', 'ENG', 'Computer Science', '컴퓨터공학과', '1995-03-01'],
    ]);

    $this->artisan('reference:import', ['type' => 'org_units', 'file' => $file, '--actor' => 'ops@example.com'])->assertSuccessful();

    $department = OrgUnit::query()->where('code', 'CSE')->sole();

    expect($department->parent()->value('code'))->toBe('ENG')
        ->and($department->getTranslations('name'))->toBe(['en' => 'Computer Science', 'ko' => '컴퓨터공학과']);

    $update = csvFile($this->directory, 'update.csv', [
        ['code', 'type', 'parent', 'name_en', 'name_zh_CN', 'effective_start_date'],
        ['CSE', 'department', 'ENG', 'Computer Science and Engineering', '计算机工程系', '1995-03-01'],
    ]);

    $this->artisan('reference:import', ['type' => 'org_units', 'file' => $update, '--actor' => 'ops@example.com'])->assertSuccessful();

    expect(OrgUnit::query()->where('code', 'CSE')->count())->toBe(1)
        ->and($department->fresh()->getTranslations('name'))
        ->toBe(['en' => 'Computer Science and Engineering', 'ko' => '컴퓨터공학과', 'zh_CN' => '计算机工程系']);
});

it('reports failed rows and imports the rest', function () {
    $file = csvFile($this->directory, 'org.csv', [
        ['code', 'type', 'parent', 'name_en', 'effective_start_date'],
        ['UNI', 'university', '', 'Sample University', '1990-03-01'],
        ['BAD', 'no_such_type', 'UNI', 'Unknown type', '1990-03-01'],
        ['NONAME', 'college', 'UNI', '', '1990-03-01'],
        ['LATE', 'college', 'UNI', 'Ends before start', '2020-03-01'],
    ]);

    $this->artisan('reference:import', ['type' => 'org_units', 'file' => $file, '--actor' => 'ops@example.com'])
        ->assertExitCode(2);

    expect(OrgUnit::query()->pluck('code')->sort()->values()->all())->toBe(['LATE', 'UNI']);
});

it('rejects a row the database refuses without losing the other rows', function () {
    $file = csvFile($this->directory, 'org.csv', [
        ['code', 'type', 'parent', 'name_en', 'effective_start_date', 'effective_end_date'],
        ['UNI', 'university', '', 'Sample University', '1990-03-01', ''],
        ['BADRANGE', 'college', 'UNI', 'Bad range', '2020-03-01', '2019-01-01'],
        ['OK', 'college', 'UNI', 'Fine', '2020-03-01', ''],
    ]);

    $this->artisan('reference:import', ['type' => 'org_units', 'file' => $file, '--actor' => 'ops@example.com'])->assertExitCode(2);

    expect(OrgUnit::query()->pluck('code')->sort()->values()->all())->toBe(['OK', 'UNI']);
});

it('imports buildings, rooms with room types, and code values', function () {
    $this->artisan('reference:import', ['type' => 'buildings', 'file' => csvFile($this->directory, 'b.csv', [
        ['code', 'name_en', 'name_ko'], ['SCI', 'Science Hall', '과학관'],
    ]), '--actor' => 'ops@example.com'])->assertSuccessful();

    $this->artisan('reference:import', ['type' => 'rooms', 'file' => csvFile($this->directory, 'r.csv', [
        ['building', 'code', 'room_type', 'capacity'], ['SCI', '101', 'lecture', '60'], ['SCI', '102', 'unknown', '30'],
    ]), '--actor' => 'ops@example.com'])->assertExitCode(2);

    $this->artisan('reference:import', ['type' => 'lookup_values', 'file' => csvFile($this->directory, 'l.csv', [
        ['type', 'code', 'name_en', 'name_ko'], ['ethnicity', 'han', 'Han', '한족'], ['ethnicity', 'korean', 'Korean', '조선족'],
    ]), '--actor' => 'ops@example.com'])->assertSuccessful();

    $room = Room::query()->with('roomType')->where('code', '101')->sole();

    expect(Building::query()->where('code', 'SCI')->sole()->name)->toBe('Science Hall')
        ->and($room->capacity)->toBe(60)
        ->and($room->roomType?->code)->toBe('lecture')
        ->and(Room::query()->where('code', '102')->exists())->toBeFalse()
        ->and(LookupValue::query()->ofType('ethnicity')->count())->toBe(2);
});

it('checks the header in a dry run without importing', function () {
    $missing = csvFile($this->directory, 'missing.csv', [['code', 'name_en'], ['UNI', 'Sample']]);
    $valid = csvFile($this->directory, 'valid.csv', [['code', 'type', 'name_en', 'effective_start_date'], ['UNI', 'university', 'Sample', '1990-03-01']]);

    $this->artisan('reference:import', ['type' => 'org_units', 'file' => $missing, '--dry-run' => true, '--actor' => 'ops@example.com'])
        ->expectsOutputToContain('Missing required columns')
        ->assertFailed();

    $this->artisan('reference:import', ['type' => 'org_units', 'file' => $valid, '--dry-run' => true, '--actor' => 'ops@example.com'])
        ->assertSuccessful();

    expect(OrgUnit::query()->count())->toBe(0);
});

it('requires an actor account', function () {
    $file = csvFile($this->directory, 'valid.csv', [['code', 'type', 'name_en', 'effective_start_date'], ['UNI', 'university', 'Sample', '1990-03-01']]);

    $this->artisan('reference:import', ['type' => 'org_units', 'file' => $file])->assertFailed();
});
