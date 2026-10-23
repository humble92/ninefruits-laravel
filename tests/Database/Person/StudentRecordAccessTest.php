<?php

/*
| Phase 2 completion criterion: who sees which student records and people, checked in
| both layers independently (porting/porting-plan.md section 7.4):
| - application: StudentRecord::visibleTo() run as the system actor (row level security off)
| - database: plain queries as the user (no application scope)
*/

use App\Domain\Person\Actions\AssignAdvisor;
use App\Domain\Security\Actions\AssignRole;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\ScopeType;
use App\Domain\Security\Services\AuthorizationService;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\Program;
use App\Models\Person\Instructor;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\Security\Role;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $eng = OrgUnit::factory()->create(['code' => 'ENG']);
    $cse = OrgUnit::factory()->under($eng)->create(['code' => 'CSE']);
    $ee = OrgUnit::factory()->under($eng)->create(['code' => 'EE']);
    $kor = OrgUnit::factory()->create(['code' => 'KOR']);

    $programs = [
        'CSE' => Program::factory()->create(['code' => 'B-CSE', 'org_unit_id' => $cse->id]),
        'EE' => Program::factory()->create(['code' => 'B-EE', 'org_unit_id' => $ee->id]),
        'KOR' => Program::factory()->create(['code' => 'B-KOR', 'org_unit_id' => $kor->id]),
    ];

    $student = fn (string $number, Program $program, ?Person $person = null) => StudentRecord::factory()
        ->inProgram($program)
        ->create(['student_number' => $number, 'person_id' => ($person ?? Person::factory()->create())->id]);

    $this->alice = $student('S-ALICE', $programs['CSE']);
    $this->bob = $student('S-BOB', $programs['EE']);
    $this->carol = $student('S-CAROL', $programs['KOR']);

    $role = fn (array $permissions) => Role::factory()->withPermissions($permissions)->create();
    $view = $role([Permission::StudentRecordsView]);
    $assign = fn (User $user, Role $role, ScopeType $type = ScopeType::Global, ?int $id = null) => app(AssignRole::class)->handle($user, $role, 'test', $type, $id);

    $this->users = [
        'registrar' => tap(User::factory()->create(), fn ($user) => $assign($user, $view)),
        'cse assistant' => tap(User::factory()->create(), fn ($user) => $assign($user, $view, ScopeType::OrgUnit, $cse->id)),
        'engineering assistant' => tap(User::factory()->create(), fn ($user) => $assign($user, $view, ScopeType::OrgUnit, $eng->id)),
        'korean program officer' => tap(User::factory()->create(), fn ($user) => $assign($user, $view, ScopeType::Program, $programs['KOR']->id)),
        'alice' => User::factory()->create(['person_id' => $this->alice->person_id]),
        'nobody' => User::factory()->create(),
    ];

    $advisor = Instructor::factory()->create(['org_unit_id' => $cse->id]);
    $this->users['advisor'] = User::factory()->create(['person_id' => $advisor->person_id]);
    app(AssignAdvisor::class)->handle($this->carol, $advisor, now()->subMonth()->toImmutable());

    app(AuthorizationService::class)->flush();
});

dataset('viewers', [
    'registrar' => ['registrar', ['S-ALICE', 'S-BOB', 'S-CAROL']],
    'cse assistant' => ['cse assistant', ['S-ALICE']],
    'engineering assistant (college includes departments)' => ['engineering assistant', ['S-ALICE', 'S-BOB']],
    'korean program officer' => ['korean program officer', ['S-CAROL']],
    'advisor of carol' => ['advisor', ['S-CAROL']],
    'student alice' => ['alice', ['S-ALICE']],
    'user without roles' => ['nobody', []],
]);

it('limits student records in the application layer', function (string $who, array $expected) {
    $visible = StudentRecord::query()->visibleTo($this->users[$who])->orderBy('student_number')->pluck('student_number')->all();

    expect($visible)->toBe($expected);
})->with('viewers');

it('limits student records in the database for the same users', function (string $who, array $expected) {
    $context = app(DatabaseSessionContext::class);
    $context->useBaseline([]);
    $context->forUser($this->users[$who]->id);

    expect(DB::table('student_records')->orderBy('student_number')->pluck('student_number')->all())->toBe($expected);

    $personIds = DB::table('people')->pluck('id')->all();

    foreach (['S-ALICE' => $this->alice, 'S-BOB' => $this->bob, 'S-CAROL' => $this->carol] as $number => $record) {
        expect(in_array($record->person_id, $personIds, true))->toBe(in_array($number, $expected, true));
    }
})->with('viewers');

it('lets only scoped managers change a record in the database', function () {
    $manager = User::factory()->create();
    app(AssignRole::class)->handle($manager, Role::factory()->withPermissions([Permission::StudentRecordsManage])->create(), 'test', ScopeType::OrgUnit, $this->alice->org_unit_id);

    $context = app(DatabaseSessionContext::class);
    $context->useBaseline([]);
    $context->forUser($manager->id);

    expect(DB::table('student_records')->where('id', $this->alice->id)->update(['year_level' => 2]))->toBe(1)
        ->and(DB::table('student_records')->where('id', $this->bob->id)->update(['year_level' => 2]))->toBe(0);

    $context->forUser($this->users['cse assistant']->id);

    expect(DB::table('student_records')->where('id', $this->alice->id)->update(['year_level' => 3]))->toBe(0);
});

it('hides people from other people unless a rule allows it', function () {
    $context = app(DatabaseSessionContext::class);
    $context->useBaseline([]);
    $context->forUser($this->users['alice']->id);

    expect(DB::table('people')->where('id', $this->bob->person_id)->exists())->toBeFalse()
        ->and(DB::table('people')->where('id', $this->alice->person_id)->exists())->toBeTrue()
        ->and(DB::table('people')->where('id', $this->users['advisor']->person_id)->exists())->toBeTrue();
});
