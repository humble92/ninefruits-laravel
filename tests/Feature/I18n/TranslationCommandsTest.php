<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->langPath = sys_get_temp_dir().'/lang-'.uniqid();
    File::ensureDirectoryExists("{$this->langPath}/en");
    File::ensureDirectoryExists("{$this->langPath}/ko");
    File::put("{$this->langPath}/en/registration.php", "<?php return ['errors' => ['credit_limit_exceeded' => 'Credit limit exceeded', 'closed' => 'Closed']];");
    File::put("{$this->langPath}/ko/registration.php", "<?php return ['errors' => ['closed' => '마감', 'removed' => '삭제됨']];");
    app()->useLangPath($this->langPath);
});

afterEach(function () {
    File::deleteDirectory($this->langPath);
});

it('reports missing and obsolete keys', function () {
    $this->artisan('i18n:missing', ['--strict' => true])
        ->expectsOutputToContain('registration.errors.credit_limit_exceeded')
        ->expectsOutputToContain('registration.errors.removed')
        ->assertExitCode(2);
});

it('exports and imports translations for translators', function () {
    $file = "{$this->langPath}/ko.json";

    $this->artisan('i18n:export', ['locale' => 'ko', 'path' => $file, '--missing' => true])->assertSuccessful();

    $document = json_decode(File::get($file), true);
    expect($document)->toBe(['registration.errors.credit_limit_exceeded' => ['source' => 'Credit limit exceeded', 'translation' => '']]);

    $document['registration.errors.credit_limit_exceeded']['translation'] = '학점 상한 초과';
    $document['unknown.key'] = ['translation' => 'ignored'];
    File::put($file, json_encode($document));

    $this->artisan('i18n:import', ['locale' => 'ko', 'path' => $file])->assertSuccessful();

    expect(require "{$this->langPath}/ko/registration.php")
        ->toBe(['errors' => ['closed' => '마감', 'removed' => '삭제됨', 'credit_limit_exceeded' => '학점 상한 초과']]);
});
