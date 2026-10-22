<?php

use App\Domain\Security\Actions\AssignRole;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\ScopeType;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\Program;
use App\Models\Security\Role;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->university = OrgUnit::factory()->create();
    $this->college = OrgUnit::factory()->under($this->university)->create();
    $this->department = OrgUnit::factory()->under($this->college)->create();
    $this->otherCollege = OrgUnit::factory()->under($this->university)->create();
});

it('returns descendants and ancestors', function () {
    expect(OrgUnit::descendantIdsOf([$this->college->id]))->toEqualCanonicalizing([$this->college->id, $this->department->id])
        ->and($this->department->ancestorIds())->toBe([$this->department->id, $this->college->id, $this->university->id]);
});

it('prevents placing a unit under itself or a descendant', function () {
    expect(databaseError(fn () => $this->college->update(['parent_id' => $this->department->id])))->toContain('cannot be placed under itself')
        ->and(databaseError(fn () => $this->college->update(['parent_id' => $this->college->id])))->toContain('cannot be placed under itself')
        ->and(databaseError(fn () => $this->college->update(['parent_id' => $this->otherCollege->id])))->toBeNull();
});

it('keeps a generated validity period for date queries', function () {
    $ended = OrgUnit::factory()->ended('2015-02-28')->create(['effective_start_date' => '2000-03-01']);

    expect(OrgUnit::query()->effectiveOn('2014-06-01')->whereKey($ended->id)->exists())->toBeTrue()
        ->and(OrgUnit::query()->effectiveOn('2015-03-01')->whereKey($ended->id)->exists())->toBeFalse()
        ->and($ended->isEffectiveOn('2015-02-28'))->toBeTrue()
        ->and(databaseError(fn () => $ended->update(['effective_end_date' => '1999-01-01'])))->toContain('range lower bound must be less than or equal to range upper bound');
});

it('gives row level security the org units of a scoped permission including descendants', function () {
    $user = User::factory()->create();
    $role = Role::factory()->withPermissions([Permission::UsersView])->create();
    app(AssignRole::class)->handle($user, $role, 'test', ScopeType::OrgUnit, $this->college->id);

    app(DatabaseSessionContext::class)->forUser($user->id);

    $ids = trim((string) DB::scalar("SELECT util.permission_org_unit_ids('users.view')"), '{}');

    expect(array_map('intval', explode(',', $ids)))->toEqualCanonicalizing([$this->college->id, $this->department->id]);
});

it('requires translatable names to be json objects', function () {
    expect(databaseError(fn () => DB::table('programs')->insert([
        'code' => 'X1', 'name' => '"plain"', 'degree_level' => 'bachelor', 'org_unit_id' => $this->department->id, 'effective_start_date' => '2020-01-01',
    ])))->toContain('programs_name_object_check')
        ->and(Program::query()->count())->toBe(0);
});
