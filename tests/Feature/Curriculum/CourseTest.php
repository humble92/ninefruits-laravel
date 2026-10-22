<?php

/*
| Phase 4 completion criterion: versions of one course never overlap (exclusion
| constraint), and revising a course keeps earlier sections on their version.
*/

use App\Domain\Curriculum\Actions\ReviseCourse;
use App\Domain\Curriculum\Enums\ProgramRole;
use App\Domain\Curriculum\Enums\RequisiteKind;
use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseRequisite;
use App\Models\Curriculum\CourseVersion;
use App\Models\Curriculum\CurriculumRequirementSet;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\Program;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Essential\EssentialSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

it('refuses overlapping versions of a course in the database', function () {
    $course = Course::factory()->withVersion(['effective_start_date' => '2020-03-01', 'effective_end_date' => '2024-02-29'])->create();

    expect(fn () => DB::transaction(fn () => CourseVersion::factory()->for($course)->between('2023-03-01')->create()))
        ->toThrow(QueryException::class, 'course_versions_no_overlap_excl');

    CourseVersion::factory()->for($course)->between('2024-03-01')->create();
    expect($course->versionOn('2025-05-01')->effective_start_date->toDateString())->toBe('2024-03-01')
        ->and($course->versionOn('2021-05-01')->effective_start_date->toDateString())->toBe('2020-03-01');
});

it('revises a course from a date, copying requisites and keeping the old version', function () {
    $prerequisite = Course::factory()->create();
    $course = Course::factory()->withVersion(['credits' => 3, 'title' => ['en' => 'Data Structures', 'ko' => '자료구조']])->create();
    $old = $course->versions()->sole();
    CourseRequisite::query()->create(['course_version_id' => $old->id, 'kind' => RequisiteKind::Prerequisite, 'required_course_id' => $prerequisite->id]);

    $new = app(ReviseCourse::class)->handle($course, CarbonImmutable::parse('2027-03-01'), ['credits' => 4], User::factory()->create(), 'Lab added');

    expect($old->refresh()->effective_end_date->toDateString())->toBe('2027-02-28')
        ->and($new->credits)->toBe('4.0')
        ->and($new->getTranslations('title'))->toBe(['en' => 'Data Structures', 'ko' => '자료구조'])
        ->and($new->requisites()->pluck('required_course_id')->all())->toBe([$prerequisite->id])
        ->and(fn () => app(ReviseCourse::class)->handle($course, CarbonImmutable::parse('2027-03-01'), [], User::factory()->create()))->toThrow(ValidationException::class);
});

it('keeps requirement sets of a program and role from overlapping in catalog years', function () {
    $program = Program::factory()->create();
    CurriculumRequirementSet::factory()->create(['program_id' => $program->id, 'catalog_year_from' => 2015, 'catalog_year_to' => 2022]);
    CurriculumRequirementSet::factory()->create(['program_id' => $program->id, 'catalog_year_from' => 2023]);
    CurriculumRequirementSet::factory()->create(['program_id' => $program->id, 'program_role' => ProgramRole::Minor, 'catalog_year_from' => 2015]);

    expect(fn () => DB::transaction(fn () => CurriculumRequirementSet::factory()->create(['program_id' => $program->id, 'catalog_year_from' => 2022])))
        ->toThrow(QueryException::class, 'curriculum_requirement_sets_no_overlap_excl')
        ->and(CurriculumRequirementSet::applicable($program->id, ProgramRole::Primary, 2024)?->catalog_year_from)->toBe(2023)
        ->and(CurriculumRequirementSet::applicable($program->id, ProgramRole::Primary, 2018)?->catalog_year_to)->toBe(2022);
});

it('imports courses and revisions from CSV, failing only bad rows', function () {
    $this->seed(EssentialSeeder::class);
    $unit = OrgUnit::factory()->create(['code' => 'CSE']);
    User::factory()->create(['email' => 'ops@example.com']);
    $file = sys_get_temp_dir().'/courses-'.uniqid().'.csv';
    file_put_contents($file, implode("\n", [
        'code,org_unit,effective_start_date,effective_end_date,title_en,title_ko,credits',
        'CSE2010,CSE,2015-03-01,2026-02-28,Data Structures,자료구조,3',
        'CSE2010,,2026-03-01,,Data Structures,자료구조,4',
        'CSE9999,NOPE,2015-03-01,,Ghost,,3',
        'CSE2010,,2020-03-01,,Overlapping,,3',
    ])."\n");

    $this->artisan('reference:import', ['type' => 'courses', 'file' => $file, '--actor' => 'ops@example.com'])->assertExitCode(2);
    unlink($file);

    $course = Course::query()->where('code', 'CSE2010')->sole();
    expect($course->org_unit_id)->toBe($unit->id)
        ->and($course->versions()->count())->toBe(2)
        ->and($course->versionOn('2026-05-01')->credits)->toBe('4.0')
        ->and(Course::query()->where('code', 'CSE9999')->exists())->toBeFalse();
});
