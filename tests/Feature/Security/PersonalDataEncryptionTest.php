<?php

use App\Domain\Security\Actions\RevealSensitiveValue;
use App\Domain\Security\Enums\SecurityEventType;
use App\Models\Concerns\HasEncryptedPii;
use App\Models\Contracts\EncryptsPersonalData;
use App\Models\Security\SecurityEvent;
use App\Models\User;
use App\Support\Pii\PiiCipher;
use App\Support\Pii\PiiType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class PiiTestRecord extends Model implements EncryptsPersonalData
{
    use HasEncryptedPii;

    protected $table = 'pii_test_records';

    protected $guarded = [];

    public function encryptedPiiAttributes(): array
    {
        return ['national_id' => PiiType::NationalId];
    }
}

// Created on the migrator connection outside the test transaction. Dropped at the start of
// the next test (not in afterEach, where the open test transaction still holds a lock).
beforeEach(function () {
    Schema::connection(config('database.migrator_connection'))->dropIfExists('pii_test_records');
    Schema::connection(config('database.migrator_connection'))->create('pii_test_records', function (Blueprint $table) {
        $table->id();
        $table->text('national_id_encrypted')->nullable();
        $table->char('national_id_hash', 64)->nullable()->index();
        $table->timestampsTz();
    });
});

it('stores personal data encrypted with the PII key, not in plain text', function () {
    $record = PiiTestRecord::query()->create(['national_id_encrypted' => '900101-1234567']);

    $raw = DB::table('pii_test_records')->where('id', $record->id)->value('national_id_encrypted');

    expect($raw)->not->toContain('1234567')
        ->and($record->fresh()->national_id_encrypted)->toBe('900101-1234567')
        ->and(fn () => decrypt($raw, false))->toThrow(DecryptException::class);
});

it('finds records by exact value through the search hash', function () {
    $record = PiiTestRecord::query()->create(['national_id_encrypted' => '900101-1234567']);
    PiiTestRecord::query()->create(['national_id_encrypted' => '850505-2345678']);

    expect(PiiTestRecord::query()->wherePii('national_id', '9001011234567')->sole()->id)->toBe($record->id);
});

it('masks values for display', function () {
    $record = PiiTestRecord::query()->create(['national_id_encrypted' => '900101-1234567']);

    expect($record->maskedPii('national_id'))->toBe('**********4567')
        ->and(PiiType::Email->mask('kim@example.com'))->toBe('k**@example.com');
});

it('reveals a value only with the view_sensitive permission and records it', function () {
    Gate::define('pii_test_records.view_sensitive', fn (User $user) => $user->email === 'officer@example.com');
    $record = PiiTestRecord::query()->create(['national_id_encrypted' => '900101-1234567']);
    $officer = User::factory()->create(['email' => 'officer@example.com']);

    $value = app(RevealSensitiveValue::class)->handle($officer, $record, 'national_id', 'pii_test_records.view_sensitive', 'Certificate check');

    $event = SecurityEvent::query()->where('type', SecurityEventType::SensitiveDataRevealed)->sole();

    expect($value)->toBe('900101-1234567')
        ->and($event->user_id)->toBe($officer->id)
        ->and($event->properties)->toEqualCanonicalizing(['attribute' => 'national_id', 'reason' => 'Certificate check']);

    expect(fn () => app(RevealSensitiveValue::class)->handle(User::factory()->create(), $record, 'national_id', 'pii_test_records.view_sensitive'))
        ->toThrow(AuthorizationException::class);
});

it('re-encrypts data with a new key after rotation', function () {
    $record = PiiTestRecord::query()->create(['national_id_encrypted' => '900101-1234567']);
    $oldKey = config('pii.key');

    config(['pii.key' => 'base64:'.base64_encode(random_bytes(32)), 'pii.previous_keys' => [$oldKey]]);
    app()->forgetInstance(PiiCipher::class);

    $before = DB::table('pii_test_records')->where('id', $record->id)->value('national_id_encrypted');
    expect(app(PiiCipher::class)->isEncryptedWithCurrentKey($before))->toBeFalse();

    $this->artisan('pii:reencrypt', ['model' => PiiTestRecord::class, '--force' => true])->assertSuccessful();

    $after = DB::table('pii_test_records')->where('id', $record->id)->value('national_id_encrypted');

    expect(app(PiiCipher::class)->isEncryptedWithCurrentKey($after))->toBeTrue()
        ->and(PiiTestRecord::query()->find($record->id)->national_id_encrypted)->toBe('900101-1234567');
});
