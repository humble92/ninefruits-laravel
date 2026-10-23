<?php

use App\Models\Offering\Section;
use App\Models\Offering\SectionInstructor;
use App\Models\Person\Instructor;
use App\Models\Registration\Enrollment;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->mine = Enrollment::factory()->inSection(Section::factory()->published()->create())->create();
    $this->theirs = Enrollment::factory()->inSection(Section::factory()->published()->create())->create();
    $instructor = Instructor::factory()->create();
    SectionInstructor::query()->create(['section_id' => $this->mine->section_id, 'instructor_id' => $instructor->id, 'role' => 'primary']);
    $this->teacher = User::factory()->create(['person_id' => $instructor->person_id]);
    $this->scaleId = Enrollment::factory()->completed()->create()->grade_scale_id;

    $context = app(DatabaseSessionContext::class);
    $context->useBaseline([]);
    $context->forUser($this->teacher->id);
});

it('lets an instructor write draft and submitted grades of their own sections only', function () {
    $draft = ['grade_status' => 'draft', 'grade_code' => 'A0', 'grade_scale_id' => $this->scaleId];

    expect(DB::table('enrollments')->where('id', $this->mine->id)->update($draft))->toBe(1)
        ->and(DB::table('enrollments')->where('id', $this->theirs->id)->update($draft))->toBe(0)
        ->and(DB::table('enrollments')->where('id', $this->mine->id)->update(['grade_status' => 'submitted']))->toBe(1)
        // Submitted grades are out of the instructor's reach.
        ->and(DB::table('enrollments')->where('id', $this->mine->id)->update(['grade_code' => 'F']))->toBe(0);
});

it('does not let an instructor finalize', function () {
    DB::table('enrollments')->where('id', $this->mine->id)->update(['grade_status' => 'draft', 'grade_code' => 'A0', 'grade_scale_id' => $this->scaleId]);

    expect(databaseError(fn () => DB::table('enrollments')->where('id', $this->mine->id)->update(['grade_status' => 'finalized', 'status' => 'completed'])))
        ->toContain('row-level security');
});

it('lets the instructor request a change for their section and nobody else for theirs', function () {
    $request = fn (Enrollment $enrollment) => databaseError(fn () => DB::table('grade_change_requests')->insert([
        'enrollment_id' => $enrollment->id, 'to_grade_code' => 'A0', 'reason' => 'x', 'status' => 'pending',
        'requested_by_id' => $this->teacher->id, 'requested_at' => now(), 'created_at' => now(), 'updated_at' => now(),
    ]));

    expect($request($this->mine))->toBeNull()
        ->and($request($this->theirs))->toContain('row-level security');
});
