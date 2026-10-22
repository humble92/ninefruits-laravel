<?php

use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

function latestRecordChange(string $table): object
{
    return DB::table('audit.record_changes')->where('table_name', $table)->latest('id')->first();
}

it('records inserts, updates and deletes with the request context', function () {
    app(DatabaseSessionContext::class)->forUser(42, 'request-1');

    $user = User::factory()->create(['name' => 'Before']);
    $user->update(['name' => 'After']);

    $change = latestRecordChange('users');

    expect($change->operation)->toBe('UPDATE')
        ->and($change->record_id)->toBe((string) $user->id)
        ->and(json_decode($change->changed_columns))->toContain('name')
        ->and(json_decode($change->old_values)->name)->toBe('Before')
        ->and(json_decode($change->new_values)->name)->toBe('After')
        ->and($change->user_id)->toBe(42)
        ->and($change->actor)->toBe('user:42')
        ->and($change->request_id)->toBe('request-1')
        ->and($change->database_user)->toBe('app_runtime');

    $user->delete();

    expect(latestRecordChange('users')->operation)->toBe('DELETE');
});

it('records changes made outside the application', function () {
    app(DatabaseSessionContext::class)->useBaseline([]);
    DB::table('users')->insert([
        'name' => 'Direct', 'email' => 'direct@example.com', 'password' => 'x',
    ]);

    $change = latestRecordChange('users');

    expect($change->operation)->toBe('INSERT')
        ->and($change->actor)->toBeNull()
        ->and(json_decode($change->new_values)->email)->toBe('direct@example.com');
});

it('redacts secret columns', function () {
    User::factory()->create();

    expect(json_decode(latestRecordChange('users')->new_values))
        ->password->toBe('[redacted]')
        ->remember_token->toBe('[redacted]');
});

it('skips updates that change nothing', function () {
    $user = User::factory()->create();
    $count = DB::table('audit.record_changes')->count();

    DB::table('users')->where('id', $user->id)->update(['name' => $user->name]);

    expect(DB::table('audit.record_changes')->count())->toBe($count);
});

it('keeps audit records append-only for the application', function () {
    User::factory()->create();

    expect(databaseError(fn () => DB::table('audit.record_changes')->delete()))->toContain('permission denied')
        ->and(databaseError(fn () => DB::table('audit.record_changes')->insert([
            'table_schema' => 'public', 'table_name' => 'users', 'operation' => 'INSERT', 'database_user' => 'forged',
        ])))->toContain('permission denied');
});

it('blocks changes to audit records even for the schema owner', function () {
    $owner = DB::connection(config('database.migrator_connection'));

    expect(fn () => $owner->table('audit.security_events')->delete())
        ->toThrow(QueryException::class, 'append-only');
});
