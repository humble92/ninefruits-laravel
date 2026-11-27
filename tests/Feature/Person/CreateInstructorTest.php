<?php

use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\Resources\Instructors\Pages\CreateInstructor;
use App\Models\Organization\OrgUnit;
use App\Models\Person\Instructor;
use App\Models\Person\Person;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('finishes saving an instructor with a new or existing person', function (string $personMode) {
    Filament::setCurrentPanel('admin');

    $organization = OrgUnit::factory()->create();
    $staff = User::factory()->withPermissions([Permission::InstructorsManage, Permission::PeopleManage, Permission::PeopleView])->create();
    $person = $personMode === 'existing' ? Person::factory()->create() : null;

    $this->actingAs($staff);

    Livewire::test(CreateInstructor::class)
        ->set('data.person_mode', $personMode)
        ->set($person ? 'data.person_id' : 'data.new_person.family_name', $person?->id ?? 'Example')
        ->set('data.employee_number', 'INSTRUCTOR-101')
        ->set('data.org_unit_id', $organization->id)
        ->set('data.employment_type', 'full_time')
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $instructor = Instructor::query()->with('person')->where('employee_number', 'INSTRUCTOR-101')->sole();

    if ($personMode === 'existing') {
        expect($instructor->person->is($person))->toBeTrue();
    } else {
        expect($instructor->person->family_name)->toBe('Example');
    }
})->with(['new', 'existing']);
