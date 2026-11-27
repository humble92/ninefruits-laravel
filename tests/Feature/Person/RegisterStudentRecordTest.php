<?php

use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\Resources\StudentRecords\Pages\RegisterStudentRecord;
use App\Filament\Admin\Resources\StudentRecords\StudentRecordResource;
use App\Models\Organization\Program;
use App\Models\Person\Instructor;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('finishes registering a student with a new person or an existing instructor', function (string $personMode) {
    Filament::setCurrentPanel('admin');

    $program = Program::factory()->create(['effective_start_date' => '2000-01-01']);
    $staff = User::factory()->withPermissions([Permission::StudentRecordsManage, Permission::PeopleManage, Permission::PeopleView])->create();
    $person = $personMode === 'existing' ? Person::factory()->create() : null;

    if ($person) {
        Instructor::factory()->create(['person_id' => $person->id]);
    }

    $this->actingAs($staff);

    $registration = Livewire::test(RegisterStudentRecord::class)
        ->set('data.person_mode', $personMode)
        ->set($person ? 'data.person_id' : 'data.new_person.family_name', $person?->id ?? 'Example')
        ->set('data.program_id', $program->id)
        ->set('data.admission_date', '2026-03-02')
        ->set('data.student_number', 'STUDENT-101')
        ->set('data.catalog_year', 2026)
        ->set('data.year_level', 1)
        ->call('create')
        ->assertHasNoFormErrors();

    $record = StudentRecord::query()->with('person')->where('student_number', 'STUDENT-101')->sole();
    $registration->assertRedirect(StudentRecordResource::getUrl('view', ['record' => $record]));

    if ($person) {
        expect($record->person->is($person))->toBeTrue();
    } else {
        expect($record->person->family_name)->toBe('Example');
    }
})->with(['new', 'existing']);
