<?php

/*
| Row changes made by queued imports and commands must be traceable to who started them
| (docs/decisions/0002-database-roles-schemas-audit.md).
*/

use App\Filament\Admin\Imports\OrgUnitImporter;
use App\Models\Organization\OrgUnit;
use App\Models\Security\Activity;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Database\Seeders\Essential\EssentialSeeder;
use Filament\Actions\Imports\ImportDispatcher;
use Filament\Actions\Imports\Jobs\ImportCsv;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(EssentialSeeder::class);
    $this->csv = sys_get_temp_dir().'/correlation-'.uniqid().'.csv';
    file_put_contents($this->csv, "code,type,name_en,effective_start_date\nAUD1,university,Audit University,2000-01-01\n");
});

afterEach(fn () => @unlink($this->csv));

function orgUnitChange(string $code): object
{
    $id = OrgUnit::query()->where('code', $code)->value('id');

    return DB::table('audit.record_changes')->where('table_name', 'org_units')->where('record_id', (string) $id)->where('operation', 'INSERT')->sole();
}

it('records the signed-in user on rows changed by an import started from the admin panel', function () {
    $staff = User::factory()->create();

    // Same dispatch as the admin panel's import action, inside an authenticated request.
    Route::middleware('web')->post('/_test/import', function () use ($staff) {
        $import = new Import;
        $import->user()->associate($staff);
        $import->forceFill(['file_name' => 'org.csv', 'file_path' => $this->csv, 'importer' => OrgUnitImporter::class, 'total_rows' => 1])->save();
        $rows = [['code' => 'AUD1', 'type' => 'university', 'name_en' => 'Audit University', 'effective_start_date' => '2000-01-01']];
        $columns = array_combine(array_keys($rows[0]), array_keys($rows[0]));

        app(ImportDispatcher::class)->dispatch($import, [$rows], $columns, [], ImportCsv::class, 'web');

        return ['user_id_after_job' => app(DatabaseSessionContext::class)->current()['app.user_id']];
    });

    app(DatabaseSessionContext::class)->useBaseline([]);
    $response = $this->actingAs($staff)->post('/_test/import');

    $change = orgUnitChange('AUD1');
    expect($change->user_id)->toBe($staff->id)
        ->and($change->actor)->toBe('system:job:'.ImportCsv::class)
        ->and($change->request_id)->toBe($response->headers->get('X-Request-Id'))
        // A job run synchronously gives the request its user context back.
        ->and($response->json('user_id_after_job'))->toBe((string) $staff->id);
});

it('ties rows changed by a command to the command run', function () {
    User::factory()->create(['email' => 'ops@example.com']);

    $this->artisan('reference:import', ['type' => 'org_units', 'file' => $this->csv, '--actor' => 'ops@example.com'])->assertSuccessful();

    $run = Activity::query()->where('log_name', 'command')->where('event', 'command.executed')->latest('id')->sole();
    $change = orgUnitChange('AUD1');

    expect($run->request_id)->toStartWith('cmd-')
        ->and($change->request_id)->toBe($run->request_id)
        // --actor is a claim by whoever runs the command, not a signed-in user.
        ->and($change->user_id)->toBeNull();
});
