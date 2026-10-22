<?php

use App\Domain\Person\Actions\CancelAdditionalProgramRequest;
use App\Domain\Person\Actions\DecideAdditionalProgramRequest;
use App\Domain\Person\Actions\EndAdditionalProgram;
use App\Domain\Person\Actions\RequestAdditionalProgram;
use App\Domain\Person\Enums\AdditionalProgramRequestStatus;
use App\Domain\Person\Enums\AdditionalProgramRole;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\ScopeType;
use App\Domain\Term\Enums\TermWindowType;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\Program;
use App\Models\Person\StudentRecord;
use App\Models\Term\Term;
use App\Models\Term\TermWindow;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use App\Support\Targeting\TargetCriteria;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->home = OrgUnit::factory()->create(['code' => 'HOME']);
    $this->target = OrgUnit::factory()->create(['code' => 'TGT']);
    $this->major = Program::factory()->create(['org_unit_id' => $this->home->id]);
    $this->second = Program::factory()->create(['org_unit_id' => $this->target->id, 'allows_second_major' => true, 'allows_minor' => true]);
    $this->closedProgram = Program::factory()->create(['org_unit_id' => $this->target->id]);

    $this->record = StudentRecord::factory()->inProgram($this->major)->create(['year_level' => 2]);
    $this->student = User::factory()->create(['person_id' => $this->record->person_id]);
    $this->term = Term::factory()->published()->between(now()->addMonths(2)->toDateString(), now()->addMonths(6)->toDateString())->create();
    TermWindow::factory()->for($this->term)->ofType(TermWindowType::AdditionalProgramApplication)
        ->between(now()->subDay(), now()->addWeek())->eligibleFor(new TargetCriteria(yearLevels: [2, 3]))->create();

    $this->approver = User::factory()->withPermissions([Permission::AdditionalProgramsApprove], ScopeType::OrgUnit, $this->target->id)->create();
});

function requestErrors(Closure $callback): array
{
    try {
        $callback();
    } catch (ValidationException $exception) {
        return array_keys($exception->errors());
    }

    return [];
}

it('takes a request in an open window and adds the program on approval from the term start', function () {
    $request = app(RequestAdditionalProgram::class)->handle($this->record, $this->second, AdditionalProgramRole::SecondMajor, 'I like it', $this->student);

    expect($request->term_id)->toBe($this->term->id)->and($request->org_unit_id)->toBe($this->target->id);

    $program = app(DecideAdditionalProgramRequest::class)->approve($request, $this->approver, 'Welcome');

    expect($program->start_date->toDateString())->toBe($this->term->start_date->toDateString())
        ->and($request->refresh()->status)->toBe(AdditionalProgramRequestStatus::Approved)
        ->and($this->record->targetProgramIds())->toContain($this->second->id)
        ->and($this->record->targetOrgUnitIds())->toContain($this->target->id);
});

it('refuses requests outside the window, for programs that do not accept them, and beyond the limit', function () {
    $request = fn (Program $program, AdditionalProgramRole $role = AdditionalProgramRole::SecondMajor) => app(RequestAdditionalProgram::class)
        ->handle($this->record->refresh(), $program, $role, null, $this->student);

    expect(requestErrors(fn () => $request($this->closedProgram)))->toBe(['program_id'])
        ->and(requestErrors(fn () => $request($this->major)))->toBe(['program_id']);

    $request($this->second);
    $another = Program::factory()->create(['allows_second_major' => true]);
    expect(requestErrors(fn () => $request($another)))->toBe(['role']); // one second major at a time

    $this->record->update(['year_level' => 4]);
    expect(requestErrors(fn () => $request($another, AdditionalProgramRole::Minor)))->toBe(['program_id']); // window is for years 2-3
});

it('lets only the target program decide, and the student cancel while pending', function () {
    $request = app(RequestAdditionalProgram::class)->handle($this->record, $this->second, AdditionalProgramRole::Minor, null, $this->student);
    $homeStaff = User::factory()->withPermissions([Permission::AdditionalProgramsApprove], ScopeType::OrgUnit, $this->home->id)->create();

    expect(fn () => app(DecideAdditionalProgramRequest::class)->approve($request, $homeStaff))->toThrow(AuthorizationException::class);

    app(CancelAdditionalProgramRequest::class)->handle($request, $this->student);
    expect($request->refresh()->status)->toBe(AdditionalProgramRequestStatus::Cancelled)
        ->and(requestErrors(fn () => app(DecideAdditionalProgramRequest::class)->approve($request, $this->approver)))->toBe(['status']);
});

it('ends a second major and allows taking it up again later', function () {
    $request = app(RequestAdditionalProgram::class)->handle($this->record, $this->second, AdditionalProgramRole::SecondMajor, null, $this->student);
    $program = app(DecideAdditionalProgramRequest::class)->approve($request, $this->approver);

    app(EndAdditionalProgram::class)->handle($program, CarbonImmutable::parse($program->start_date)->addMonths(5), 'Gave it up', $this->approver);

    $again = app(RequestAdditionalProgram::class)->handle($this->record->refresh(), $this->second, AdditionalProgramRole::SecondMajor, null, $this->student);
    expect(app(DecideAdditionalProgramRequest::class)->approve($again, $this->approver)->end_date)->toBeNull();
});

it('shows the target department its applicants and second majors, in both layers', function () {
    $request = app(RequestAdditionalProgram::class)->handle($this->record, $this->second, AdditionalProgramRole::SecondMajor, null, $this->student);
    $targetViewer = User::factory()->withPermissions([Permission::StudentRecordsView], ScopeType::OrgUnit, $this->target->id)->create();

    $context = app(DatabaseSessionContext::class);
    $context->useBaseline([]);

    // Approver: sees the applicant's record while the request is pending.
    $context->forUser($this->approver->id);
    expect(DB::table('student_records')->where('id', $this->record->id)->exists())->toBeTrue()
        ->and(DB::table('additional_program_requests')->where('id', $request->id)->exists())->toBeTrue();
    expect(StudentRecord::query()->visibleTo($this->approver)->whereKey($this->record->id)->exists())->toBeTrue();

    // Target department records staff: not before approval, yes after.
    $context->forUser($targetViewer->id);
    expect(DB::table('student_records')->where('id', $this->record->id)->exists())->toBeFalse()
        ->and(StudentRecord::query()->visibleTo($targetViewer)->whereKey($this->record->id)->exists())->toBeFalse();

    $context->useBaseline(['app.actor' => 'system:test']);
    app(DecideAdditionalProgramRequest::class)->approve($request, $this->approver);
    $context->useBaseline([]);
    $context->forUser($targetViewer->id);

    expect(DB::table('student_records')->where('id', $this->record->id)->exists())->toBeTrue()
        ->and(StudentRecord::query()->visibleTo($targetViewer)->whereKey($this->record->id)->exists())->toBeTrue();
});
