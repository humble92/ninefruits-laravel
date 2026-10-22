<?php

use App\Domain\Offering\Actions\SaveSectionMeeting;
use App\Domain\Registration\Actions\EnrollStudent;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Registration\Enums\WaitlistStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Term\Enums\TermWindowType;
use App\Livewire\Portal\Registration;
use App\Models\Facility\Room;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\CartItem;
use App\Models\Registration\CreditLimitPolicy;
use App\Models\Registration\Enrollment;
use App\Models\Registration\WaitlistEntry;
use App\Models\Term\Term;
use App\Models\Term\TermWindow;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->term = Term::factory()->published()->between(now()->addMonth()->toDateString(), now()->addMonths(5)->toDateString())->create();
    TermWindow::factory()->for($this->term)->ofType(TermWindowType::Registration)->between(now()->subDay(), now()->addWeek())->create();
    CreditLimitPolicy::query()->create(['name' => ['en' => 'Standard'], 'max_credits' => 18]);
    $planner = User::factory()->withPermissions([Permission::SectionsManage])->create();

    $this->section = Section::factory()->published()->inTerm($this->term)->create(['capacity' => 1, 'waitlist_capacity' => 3]);
    app(SaveSectionMeeting::class)->handle($this->section, ['weekday' => 2, 'start_time' => '10:30', 'end_time' => '11:45', 'room_id' => Room::factory()->create()->id], $planner);
    $this->section->load('course');

    $this->record = StudentRecord::factory()->create();
    $this->student = User::factory()->create(['person_id' => $this->record->person_id]);
});

it('finds a section, registers and drops it', function () {
    $page = Livewire::actingAs($this->student)->test(Registration::class)
        ->assertSet('termId', $this->term->id)
        ->assertSee(__('registration.portal.registration_open'))
        ->set('search', $this->section->course->code)
        ->assertSee($this->section->label())
        ->call('enroll', $this->section->id)
        ->assertSet('messageTone', 'success');

    $enrollment = Enrollment::query()->where('student_record_id', $this->record->id)->sole();
    $page->assertSee(__('registration.portal.my_registrations'))->assertSeeInOrder([__('registration.portal.credits'), (string) (float) $enrollment->credits, '/ 18']);

    $page->call('drop', $enrollment->id)->assertSet('messageTone', 'success');
    expect($enrollment->refresh()->status)->toBe(EnrollmentStatus::Dropped);
});

it('registers the cart and reports each section', function () {
    Livewire::actingAs($this->student)->test(Registration::class)
        ->call('addToCart', $this->section->id)
        ->assertSee(__('registration.portal.added_to_cart'))
        ->call('submitCart')
        ->assertSee($this->section->label().': '.__('registration.portal.registered'));

    expect(Enrollment::query()->where('student_record_id', $this->record->id)->count())->toBe(1)
        ->and(CartItem::query()->count())->toBe(0);
});

it('joins and leaves the waitlist of a full section', function () {
    $other = StudentRecord::factory()->create();
    app(EnrollStudent::class)->handle($other, $this->section, User::factory()->create(['person_id' => $other->person_id]));

    $page = Livewire::actingAs($this->student)->test(Registration::class)
        ->set('search', $this->section->course->code)
        ->assertSee(__('registration.portal.join_waitlist'))
        ->call('enroll', $this->section->id)
        ->assertSet('messageTone', 'error')
        ->call('enroll', $this->section->id, true)
        ->assertSet('messageTone', 'info')
        ->assertSee(__('registration.portal.waiting', ['position' => 1]));

    $entry = WaitlistEntry::query()->where('student_record_id', $this->record->id)->sole();
    $page->call('leaveWaitlist', $entry->id);

    expect($entry->refresh()->status)->toBe(WaitlistStatus::Removed);
});

it('does not act on another student\'s registration', function () {
    $other = StudentRecord::factory()->create();
    $enrollment = app(EnrollStudent::class)
        ->handle($other, $this->section, User::factory()->create(['person_id' => $other->person_id]))->enrollment;

    Livewire::actingAs($this->student)->test(Registration::class)->call('drop', $enrollment->id)->assertNotFound();
});
