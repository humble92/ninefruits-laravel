<?php

use App\Domain\Grading\Enums\GradeChangeStatus;
use App\Models\Grading\GradeChangeRequest;
use App\Models\Registration\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->enrollment = Enrollment::factory()->completed('B0')->create();
    $this->other = Enrollment::factory()->completed('C0')->create();
});

function approvedRequest(Enrollment $enrollment, string $to = 'A0'): GradeChangeRequest
{
    $request = GradeChangeRequest::query()->create([
        'enrollment_id' => $enrollment->id, 'from_grade_code' => $enrollment->grade_code, 'to_grade_code' => $to,
        'reason' => 'Calculation error', 'requested_by_id' => User::factory()->create()->id, 'requested_at' => now(),
    ]);
    $request->forceFill(['status' => GradeChangeStatus::Approved, 'decided_by_id' => User::factory()->create()->id, 'decided_at' => now()])->save();

    return $request;
}

it('refuses a direct change to a finalized grade', function (array $change) {
    $error = databaseError(fn () => DB::table('enrollments')->where('id', $this->enrollment->id)->update($change));

    expect($error)->toContain('changes only through an approved grade change request')
        ->and(DB::table('enrollments')->where('id', $this->enrollment->id)->value('grade_code'))->toBe('B0');
})->with([
    'grade' => [['grade_code' => 'A+', 'grade_points' => 4.5]],
    'pass flag' => [['is_passed' => false]],
    'back to draft' => [['grade_status' => 'draft', 'status' => 'enrolled']],
    'credits' => [['credits' => 1]],
    'status' => [['status' => 'withdrawn']],
]);

it('refuses deleting a finalized enrollment', function () {
    expect(databaseError(fn () => DB::table('enrollments')->where('id', $this->enrollment->id)->delete()))
        ->toContain('changes only through an approved grade change request');
});

it('lets other columns of a finalized enrollment change', function () {
    expect(DB::table('enrollments')->where('id', $this->enrollment->id)->update(['changed_by_id' => null, 'updated_at' => now()]))->toBe(1);
});

it('applies a change only with an approved, unapplied request for the same enrollment', function () {
    $request = approvedRequest($this->enrollment);
    $pending = GradeChangeRequest::query()->create([
        'enrollment_id' => $this->other->id, 'to_grade_code' => 'A0', 'reason' => 'x', 'requested_by_id' => User::factory()->create()->id, 'requested_at' => now(),
    ]);

    $update = fn (int $requestId, int $enrollmentId, string $grade = 'A0') => databaseError(function () use ($requestId, $enrollmentId, $grade) {
        DB::select("SELECT set_config('app.grade_change_request_id', ?, true)", [(string) $requestId]);
        DB::table('enrollments')->where('id', $enrollmentId)->update(['grade_code' => $grade]);
    });

    // A pending request, or an approved one for another enrollment, does not open the guard.
    expect($update($pending->id, $this->other->id))->toContain('approved grade change request')
        ->and($update($request->id, $this->other->id))->toContain('approved grade change request')
        ->and($update($request->id, $this->enrollment->id))->toBeNull()
        ->and(DB::table('enrollments')->where('id', $this->enrollment->id)->value('grade_code'))->toBe('A0');

    // Once applied, the same request does not work again.
    $request->forceFill(['applied_at' => now()])->save();
    expect($update($request->id, $this->enrollment->id, 'A+'))->toContain('approved grade change request');
});

it('refuses a request decided by its own requester', function () {
    $user = User::factory()->create();
    $request = GradeChangeRequest::query()->create([
        'enrollment_id' => $this->enrollment->id, 'to_grade_code' => 'A0', 'reason' => 'x', 'requested_by_id' => $user->id, 'requested_at' => now(),
    ]);

    expect(databaseError(fn () => DB::table('grade_change_requests')->where('id', $request->id)
        ->update(['status' => 'approved', 'decided_by_id' => $user->id, 'decided_at' => now()])))
        ->toContain('grade_change_requests_second_person_check');
});
