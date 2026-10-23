<?php

use App\Domain\Lms\Actions\PullGrades;
use App\Domain\Lms\Actions\ReconcilePlatform;
use App\Domain\Lms\Connectors\NullConnector;
use App\Domain\Lms\Contracts\LmsConnector;
use App\Domain\Lms\Enums\LinkStatus;
use App\Domain\Lms\Enums\SyncOperation;
use App\Domain\Lms\Enums\SyncStatus;
use App\Domain\Lms\Jobs\SyncEnrollmentJob;
use App\Domain\Lms\Jobs\SyncSectionJob;
use App\Domain\Offering\Actions\ChangeSectionStatus;
use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Registration\Actions\DropEnrollment;
use App\Domain\Security\Actions\SyncPermissionCatalog;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\Resources\Lms\CourseLinks\LmsCourseLinkResource;
use App\Filament\Admin\Resources\Lms\Platforms\LmsPlatformResource;
use App\Filament\Admin\Resources\Lms\Platforms\Pages\CreateLmsPlatform;
use App\Filament\Admin\Resources\Lms\SyncLogs\LmsSyncLogResource;
use App\Livewire\Portal\GradeEntry;
use App\Models\Grading\GradeScale;
use App\Models\Lms\LmsCourseLink;
use App\Models\Lms\LmsPlatform;
use App\Models\Lms\LmsSyncLog;
use App\Models\Lms\LmsUserLink;
use App\Models\Offering\Section;
use App\Models\Offering\SectionInstructor;
use App\Models\Person\Instructor;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Database\Seeders\Essential\EssentialSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
    $this->seed(EssentialSeeder::class);
    app(SyncPermissionCatalog::class)->handle();
    Filament::setCurrentPanel('admin');
    GradeScale::factory()->korean()->create();

    $this->admin = User::factory()->withPermissions([Permission::AdminPanelAccess, Permission::LmsManage, Permission::SectionsManage, Permission::RegistrationManage])->create();
    $this->platform = LmsPlatform::query()->create(['code' => 'TEST', 'name' => ['en' => 'Test LMS', 'ko' => '테스트 LMS', 'zh_CN' => '测试 LMS'], 'driver' => 'null', 'base_url' => 'https://lms.test', 'is_active' => true, 'should_pull_grades' => true]);
    $this->term = Term::factory()->published()->between(now()->subMonth()->toDateString(), now()->addMonths(3)->toDateString())->create();
    $this->section = Section::factory()->inTerm($this->term)->create(['status' => SectionStatus::Draft, 'capacity' => 10]);
    $instructor = Instructor::factory()->create();
    SectionInstructor::query()->create(['section_id' => $this->section->id, 'instructor_id' => $instructor->id, 'role' => 'primary']);
    $this->teacher = User::factory()->create(['person_id' => $instructor->person_id]);
    $this->record = StudentRecord::factory()->create();
    $this->student = User::factory()->create(['person_id' => $this->record->person_id]);
});

it('queues section and roster synchronization from the domain events', function () {
    Queue::fake([SyncSectionJob::class, SyncEnrollmentJob::class]);
    app(ChangeSectionStatus::class)->handle($this->section, SectionStatus::Published, $this->admin);
    Queue::assertPushed(SyncSectionJob::class, fn (SyncSectionJob $job) => $job->platformId === $this->platform->id && $job->sectionId === $this->section->id);

    $enrollment = Enrollment::factory()->inSection($this->section->refresh())->create(['student_record_id' => $this->record->id]);
    app(DropEnrollment::class)->drop($enrollment, $this->admin, 'Staff drop');
    Queue::assertPushed(SyncEnrollmentJob::class, fn (SyncEnrollmentJob $job) => $job->enrollmentId === $enrollment->id && $job->removed);

    // An inactive platform gets nothing.
    $this->platform->forceFill(['is_active' => false])->save();
    app(ChangeSectionStatus::class)->handle($this->section->refresh(), SectionStatus::Draft, $this->admin);
    app(ChangeSectionStatus::class)->handle($this->section->refresh(), SectionStatus::Published, $this->admin);
    Queue::assertPushed(SyncSectionJob::class, 1);
});

it('links the course, its members and the user, logs every step, and reconciles', function () {
    app(ChangeSectionStatus::class)->handle($this->section, SectionStatus::Published, $this->admin);
    Enrollment::factory()->inSection($this->section->refresh())->create(['student_record_id' => $this->record->id]);

    $this->artisan('lms:sync', ['platform' => 'NOPE'])->assertFailed();
    $this->artisan('lms:sync', ['platform' => 'TEST', '--dry-run' => true])->expectsOutputToContain('Dry run: 1 section(s), 0 member(s), 0 failed.')->assertSuccessful();
    expect(LmsSyncLog::query()->where('status', SyncStatus::Skipped)->count())->toBe(1);

    $this->artisan('lms:sync', ['platform' => 'TEST', '--term' => $this->term->code, '--actor' => $this->admin->email])->expectsOutputToContain('1 section(s), 2 member(s), 0 failed.')->assertSuccessful();
    $link = LmsCourseLink::query()->where('section_id', $this->section->id)->sole();
    expect($link)->status->toBe(LinkStatus::Linked)->external_course_id->toBe('course-'.$this->section->id)->external_url->toContain('https://lms.test/')
        ->and(LmsUserLink::query()->where('user_id', $this->student->id)->sole()->external_user_id)->toBe('user-'.$this->student->id)
        // Publishing already synchronized the course and the instructor through the event (queue is sync in tests).
        ->and(LmsSyncLog::query()->where('operation', SyncOperation::EnrollmentAdd)->where('status', SyncStatus::Succeeded)->count())->toBe(3)
        ->and(LmsSyncLog::query()->where('operation', SyncOperation::CourseSync)->where('status', SyncStatus::Succeeded)->count())->toBe(2);

    // The null connector reports nobody on the platform: reconcile adds everyone back and logs the difference.
    $summary = app(ReconcilePlatform::class)->handle($this->platform, $this->term);
    expect($summary)->toMatchArray(['sections' => 1, 'added' => 2, 'removed' => 0, 'failed' => 0])
        ->and(LmsSyncLog::query()->where('operation', SyncOperation::Reconcile)->sole()->payload['missing'])->toHaveCount(2);
    $this->artisan('lms:reconcile', ['platform' => 'TEST', '--dry-run' => true])->assertSuccessful();
    $this->artisan('lms:reconcile', ['platform' => 'NOPE'])->assertFailed();

    // A driver without a connector fails the sync and marks the link.
    $this->platform->forceFill(['driver' => 'moodle'])->save();
    $this->artisan('lms:sync', ['platform' => 'TEST', '--term' => $this->term->code])->assertExitCode(2);
    expect($link->refresh()->status)->toBe(LinkStatus::Failed)->and($link->last_error)->toContain('moodle');
});

it('loads platform scores into the grade entry screen as a draft', function () {
    app(ChangeSectionStatus::class)->handle($this->section, SectionStatus::Published, $this->admin);
    $enrollment = Enrollment::factory()->inSection($this->section->refresh())->create(['student_record_id' => $this->record->id]);
    $this->artisan('lms:sync', ['platform' => 'TEST', '--term' => $this->term->code]);

    $this->section->forceFill(['evaluation_method' => 'absolute'])->save();
    app()->bind(LmsConnector::class, fn () => new class extends NullConnector
    {
        public function finalScores(LmsPlatform $platform, string $courseId): array
        {
            return LmsUserLink::query()->pluck('external_user_id')->mapWithKeys(fn (string $id) => [$id => 87.5])->all();
        }
    });
    app()->bind(NullConnector::class, fn () => app(LmsConnector::class));

    expect(app(PullGrades::class)->handle($this->platform, $this->section))->toBe([$enrollment->id => 87.5]);
    Livewire::actingAs($this->teacher)->test(GradeEntry::class, ['section' => $this->section])->assertSee(__('grading.entry.import_lms'))
        ->call('importLmsScores')->assertSet("grades.{$enrollment->id}.score", '87.5')->assertSee(__('grading.entry.lms_imported', ['count' => 1]));
    expect($enrollment->refresh()->score)->toBeNull();
});

it('shows the course link in the portal and restricts links and logs by permission and RLS', function () {
    app(ChangeSectionStatus::class)->handle($this->section, SectionStatus::Published, $this->admin);
    Enrollment::factory()->inSection($this->section->refresh())->create(['student_record_id' => $this->record->id]);
    $this->artisan('lms:sync', ['platform' => 'TEST', '--term' => $this->term->code]);

    $this->actingAs($this->student)->get(route('portal.sections.show', $this->section))->assertOk()->assertSee('https://lms.test/');
    $this->actingAs($this->teacher)->get(route('portal.teaching'))->assertOk()->assertSee('https://lms.test/');

    foreach ([LmsPlatformResource::getUrl('index'), LmsPlatformResource::getUrl('edit', ['record' => $this->platform]), LmsCourseLinkResource::getUrl('index'), LmsSyncLogResource::getUrl('index')] as $url) {
        $this->actingAs($this->admin)->get($url)->assertOk();
    }
    Livewire::actingAs($this->admin)->test(CreateLmsPlatform::class)
        ->fillForm(['code' => 'MOODLE', 'driver' => 'moodle', 'name' => ['en' => 'Moodle', 'ko' => '무들', 'zh_CN' => 'Moodle'], 'base_url' => 'https://moodle.test', 'credentials' => ['token' => 'secret'], 'is_active' => false])
        ->call('create')->assertHasNoFormErrors();
    $moodle = LmsPlatform::query()->where('code', 'MOODLE')->sole();
    expect($moodle->credentials)->toBe(['token' => 'secret'])->and($moodle->toArray())->not->toHaveKey('credentials');

    $context = app(DatabaseSessionContext::class);
    $context->forUser($this->student->id);
    expect(LmsCourseLink::query()->count())->toBe(1)->and(LmsUserLink::query()->count())->toBe(1)->and(LmsSyncLog::query()->count())->toBe(0);
    $context->forUser($this->admin->id);
    expect(LmsSyncLog::query()->count())->toBeGreaterThan(0);
});
