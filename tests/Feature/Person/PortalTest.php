<?php

use App\Domain\Person\Enums\StatusChangeStatus;
use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Settings\AuthenticationSettings;
use App\Livewire\Portal\MyProfile;
use App\Livewire\Portal\MyRecords;
use App\Livewire\Portal\SecuritySettings;
use App\Models\Person\Guardian;
use App\Models\Person\StudentRecord;
use App\Models\Person\StudentStatusChange;
use App\Models\Person\StudentStatusChangeType;
use App\Models\Security\UserIdentity;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Database\Seeders\Demo\DemoPeopleSeeder;
use Database\Seeders\Demo\DemoReferenceSeeder;
use Database\Seeders\Essential\EssentialSeeder;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;

beforeEach(function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
    $this->seed([EssentialSeeder::class, DemoReferenceSeeder::class, DemoPeopleSeeder::class]);
    $this->student = User::query()->where('email', 'student@demo.test')->sole();
});

function demoUser(string $local): User
{
    return User::query()->where('email', "{$local}@demo.test")->sole();
}

it('shows each persona its own pages under row level security', function () {
    app(DatabaseSessionContext::class)->useBaseline([]);
    $record = StudentRecord::query()->toBase()->where('person_id', $this->student->person_id)->value('student_number');
    $other = StudentRecord::query()->toBase()->where('person_id', '!=', $this->student->person_id)->value('student_number');

    $this->actingAs($this->student)->get('/portal')->assertOk()->assertSee(__('portal.navigation.records'))->assertDontSee(__('portal.navigation.advisees'));
    $this->actingAs($this->student)->get('/portal/records')->assertOk()->assertSee($record)->assertDontSee($other);
    $this->actingAs($this->student)->get('/portal/profile')->assertOk();

    $instructor = demoUser('instructor');
    $this->actingAs($instructor)->get('/portal')->assertSee(__('portal.navigation.advisees'));
    $this->actingAs($instructor)->get('/portal/advisees')->assertOk()->assertSee($record);
});

it('lets a student request a leave of absence and cancel it', function () {
    $record = StudentRecord::query()->where('person_id', $this->student->person_id)->sole();
    $leave = StudentStatusChangeType::query()->where('code', 'leave_of_absence')->sole();

    Livewire::actingAs($this->student)->test(MyRecords::class)
        ->assertSee($leave->name)
        ->set('statusChangeTypeId', $leave->id)
        ->set('reason', 'Family matters')
        ->call('requestChange')
        ->assertHasNoErrors()
        ->assertSee(__('portal.records.cancel'));

    $change = StudentStatusChange::query()->where('student_record_id', $record->id)->sole();
    expect($change->status)->toBe(StatusChangeStatus::Requested)
        ->and($change->requested_by_id)->toBe($this->student->id);

    Livewire::actingAs($this->student)->test(MyRecords::class)->call('cancel', $change->id);

    expect($change->fresh()->status)->toBe(StatusChangeStatus::Cancelled)
        ->and($record->fresh()->status)->toBe(StudentStatus::Enrolled);
});

it('does not let a student cancel someone else\'s request', function () {
    $someone = StudentStatusChange::query()->where('status', StatusChangeStatus::Requested)->first();

    Livewire::actingAs($this->student)->test(MyRecords::class)->call('cancel', $someone->id)->assertNotFound();
});

it('lets a readmitted graduate switch records and only request on the current one', function () {
    $user = demoUser('readmitted');
    $records = StudentRecord::query()->where('person_id', $user->person_id)->orderBy('admission_date')->get();
    expect($records)->toHaveCount(2);

    $component = Livewire::actingAs($user)->test(MyRecords::class)
        ->assertSee(__('portal.records.choose'))
        ->assertSee(__('portal.records.request_title'));

    $component->call('choose', $records[0]->id)
        ->assertSee($records[0]->status->label())
        ->assertDontSee(__('portal.records.request_title'));
});

it('lets a graduate see records read only', function () {
    Livewire::actingAs(demoUser('alumni'))->test(MyRecords::class)
        ->assertSee(StudentStatus::Graduated->label())
        ->assertDontSee(__('portal.records.request_title'));
});

it('updates own contact details and guardians but not the name', function () {
    $person = $this->student->person;

    Livewire::actingAs($this->student)->test(MyProfile::class)
        ->set('contact.phone', '010-1234-5678')
        ->set('contact.family_name', 'Changed')
        ->call('saveContact')
        ->set('guardian.full_name', 'Kim Mother')
        ->set('guardian.relationship', 'mother')
        ->call('addGuardian')
        ->assertHasNoErrors();

    $person->refresh();
    $guardian = Guardian::query()->where('person_id', $person->id)->where('full_name', 'Kim Mother')->sole();

    expect($person->phone)->toBe('010-1234-5678')
        ->and($person->family_name)->not->toBe('Changed');

    $foreign = Guardian::query()->where('person_id', '!=', $person->id)->first();
    Livewire::actingAs($this->student)->test(MyProfile::class)->call('removeGuardian', $foreign->id)->assertNotFound();
    Livewire::actingAs($this->student)->test(MyProfile::class)->call('removeGuardian', $guardian->id);

    expect(Guardian::query()->whereKey($guardian->id)->exists())->toBeFalse();
});

it('unlinks a school sign-in after a recent password confirmation', function () {
    app(AuthenticationSettings::class)->fill(['sso_providers' => [[
        'key' => 'school', 'label' => 'School', 'issuer' => 'https://sso.example.test', 'client_id' => 'id',
        'client_secret' => Crypt::encryptString('secret'), 'scopes' => 'openid email', 'trusts_mfa' => false, 'is_enabled' => true,
    ]]])->save();
    $identity = UserIdentity::query()->create(['user_id' => $this->student->id, 'provider' => 'school', 'subject' => 'abc', 'linked_at' => now()]);

    Livewire::actingAs($this->student)->test(SecuritySettings::class)
        ->assertSee(__('security.sso.unlink'))
        ->call('unlinkIdentity', $identity->id)
        ->assertRedirect(route('password.confirm'));

    expect(UserIdentity::query()->whereKey($identity->id)->exists())->toBeTrue();

    session()->put('auth.password_confirmed_at', now()->unix());
    Livewire::actingAs($this->student)->test(SecuritySettings::class)->call('unlinkIdentity', $identity->id);

    expect(UserIdentity::query()->whereKey($identity->id)->exists())->toBeFalse();
});
