<?php

/*
| Who sees and changes which applications, checked in both layers independently:
| application scope (Application::visibleTo) and row level security (plain queries).
*/

use App\Domain\Security\Actions\AssignRole;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\ScopeType;
use App\Domain\Security\Services\AuthorizationService;
use App\Models\Admission\AdmissionCycle;
use App\Models\Admission\Application;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\Program;
use App\Models\Person\Person;
use App\Models\Security\Role;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $eng = OrgUnit::factory()->create(['code' => 'ENG']);
    $cse = OrgUnit::factory()->under($eng)->create(['code' => 'CSE']);
    $kor = OrgUnit::factory()->create(['code' => 'KOR']);
    $cseProgram = Program::factory()->create(['code' => 'B-CSE', 'org_unit_id' => $cse->id]);
    $korProgram = Program::factory()->create(['code' => 'B-KOR', 'org_unit_id' => $kor->id]);
    $cycle = AdmissionCycle::factory()->create();

    $apply = function (string $number, Program $program) use ($cycle): Application {
        $application = Application::factory()->forCycle($cycle, $program)->submitted()->create(['person_id' => Person::factory()->create()->id]);
        $application->forceFill(['application_number' => $number])->save();

        return $application;
    };

    $this->cseApplication = $apply('A-CSE', $cseProgram);
    $this->korApplication = $apply('A-KOR', $korProgram);

    $role = fn (array $permissions) => Role::factory()->withPermissions($permissions)->create();
    $assign = fn (User $user, Role $role, ScopeType $type = ScopeType::Global, ?int $id = null) => app(AssignRole::class)->handle($user, $role, 'test', $type, $id);

    $this->users = [
        'admissions office' => tap(User::factory()->create(), fn ($user) => $assign($user, $role([Permission::ApplicationsReview]))),
        'engineering reviewer' => tap(User::factory()->create(), fn ($user) => $assign($user, $role([Permission::ApplicationsReview]), ScopeType::OrgUnit, $eng->id)),
        'korean program viewer' => tap(User::factory()->create(), fn ($user) => $assign($user, $role([Permission::ApplicationsView]), ScopeType::Program, $korProgram->id)),
        'cse applicant' => User::factory()->create(['person_id' => $this->cseApplication->person_id]),
        'nobody' => User::factory()->create(),
    ];

    app(AuthorizationService::class)->flush();
});

dataset('application viewers', [
    'admissions office' => ['admissions office', ['A-CSE', 'A-KOR']],
    'engineering reviewer (college includes departments)' => ['engineering reviewer', ['A-CSE']],
    'korean program viewer' => ['korean program viewer', ['A-KOR']],
    'applicant' => ['cse applicant', ['A-CSE']],
    'user without roles' => ['nobody', []],
]);

it('limits applications in the application layer', function (string $who, array $expected) {
    expect(Application::query()->visibleTo($this->users[$who])->orderBy('application_number')->pluck('application_number')->all())->toBe($expected);
})->with('application viewers');

it('limits applications and applicants in the database for the same users', function (string $who, array $expected) {
    $context = app(DatabaseSessionContext::class);
    $context->useBaseline([]);
    $context->forUser($this->users[$who]->id);

    expect(DB::table('applications')->orderBy('application_number')->pluck('application_number')->all())->toBe($expected);

    $people = DB::table('people')->pluck('id')->all();
    expect(in_array($this->cseApplication->person_id, $people, true))->toBe(in_array('A-CSE', $expected, true))
        ->and(in_array($this->korApplication->person_id, $people, true))->toBe(in_array('A-KOR', $expected, true));
})->with('application viewers');

it('does not let applicants decide their own application in the database', function () {
    $context = app(DatabaseSessionContext::class);
    $context->useBaseline([]);
    $context->forUser($this->users['cse applicant']->id);

    // Own savepoint: the refused statement would otherwise abort the test transaction.
    expect(fn () => DB::transaction(fn () => DB::table('applications')->where('id', $this->cseApplication->id)->update(['status' => 'admitted'])))
        ->toThrow(QueryException::class, 'row-level security');

    expect(DB::table('applications')->where('id', $this->cseApplication->id)->update(['status' => 'withdrawn', 'withdrawn_at' => now()]))->toBe(1);

    $context->forUser($this->users['korean program viewer']->id);
    expect(DB::table('applications')->where('id', $this->korApplication->id)->update(['status' => 'admitted']))->toBe(0);

    $context->forUser($this->users['engineering reviewer']->id);
    expect(DB::table('applications')->where('id', $this->cseApplication->id)->update(['decision_note' => 'ok']))->toBe(1)
        ->and(DB::table('applications')->where('id', $this->korApplication->id)->update(['decision_note' => 'ok']))->toBe(0);
});
