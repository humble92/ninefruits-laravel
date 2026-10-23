<?php

use App\Domain\Certificate\Actions\IssueCertificate;
use App\Domain\Certificate\Enums\CertificateIssueStatus;
use App\Domain\Certificate\Services\CertificateRenderer;
use App\Domain\Certificate\Services\CertificateVerification;
use App\Domain\Certificate\Settings\CertificateSettings;
use App\Domain\Registration\Enums\HoldType;
use App\Domain\Security\Actions\SyncPermissionCatalog;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\Pages\ManageCertificateSettings;
use App\Filament\Admin\Resources\Certificate\Issues\CertificateIssueResource;
use App\Filament\Admin\Resources\Certificate\Issues\Pages\ListCertificateIssues;
use App\Filament\Admin\Resources\Certificate\Types\CertificateTypeResource;
use App\Filament\Admin\Resources\StudentRecords\Pages\ViewStudentRecord;
use App\Livewire\Portal\MyCertificates;
use App\Models\Certificate\CertificateIssue;
use App\Models\Certificate\CertificateType;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\Registration\RegistrationHold;
use App\Models\Security\Activity;
use App\Models\Term\Term;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Database\Seeders\Essential\EssentialSeeder;
use Database\Seeders\Profiles\KoreanUniversityProfileSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake(config('media-library.disk_name'));
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
    $this->seed([EssentialSeeder::class, KoreanUniversityProfileSeeder::class]);
    app(SyncPermissionCatalog::class)->handle();
    Filament::setCurrentPanel('admin');

    $this->officer = User::factory()->withPermissions([Permission::AdminPanelAccess, Permission::CertificatesIssue, Permission::CertificatesManage, Permission::StudentRecordsView])->create();
    $this->record = StudentRecord::factory()->create();
    $this->enrollment = CertificateType::query()->where('code', 'ENR')->sole();
    $this->graduation = CertificateType::query()->where('code', 'GRD')->sole();
    $this->transcript = CertificateType::query()->where('code', 'TRN')->sole();
    $this->issue = app(IssueCertificate::class);
});

it('issues with sequential serial numbers, stores the PDF and records an audit entry', function () {
    $first = $this->issue->handle($this->record, $this->enrollment, 'ko', $this->officer, '장학금 신청', 2);
    $second = $this->issue->handle($this->record, $this->enrollment, 'en', $this->officer);

    expect($first->serial_number)->toBe('ENR-'.now()->year.'-000001')
        ->and($second->serial_number)->toBe('ENR-'.now()->year.'-000002')
        ->and($first->verification_code)->toHaveLength(20)
        ->and($first->copies)->toBe(2)
        ->and($first->status)->toBe(CertificateIssueStatus::Issued)
        ->and($first->pdf_checksum)->toHaveLength(64);

    $media = $first->getFirstMedia(CertificateIssue::PDF_COLLECTION);
    expect($media)->not->toBeNull()
        ->and(hash('sha256', file_get_contents($media->getPath())))->toBe($first->pdf_checksum)
        ->and(Activity::query()->where('event', 'certificate.issued')->count())->toBe(2);

    // A graduation certificate for a graduate counts separately.
    $graduate = StudentRecord::factory()->graduated()->create();
    expect($this->issue->handle($graduate, $this->graduation, 'en', $this->officer)->serial_number)->toBe('GRD-'.now()->year.'-000001');
});

it('enforces the audience, active kinds, holds and permissions', function () {
    expect(fn () => $this->issue->handle($this->record, $this->graduation, 'ko', $this->officer))->toThrow(ValidationException::class);

    $graduate = StudentRecord::factory()->graduated()->create();
    expect(fn () => $this->issue->handle($graduate, $this->enrollment, 'ko', $this->officer))->toThrow(ValidationException::class);

    $this->enrollment->forceFill(['is_active' => false])->save();
    expect(fn () => $this->issue->handle($this->record, $this->enrollment, 'ko', $this->officer))->toThrow(ValidationException::class);
    $this->enrollment->forceFill(['is_active' => true])->save();

    $hold = RegistrationHold::query()->create([
        'student_record_id' => $this->record->id, 'hold_type' => HoldType::Administrative, 'blocks_registration' => false, 'blocks_certificates' => true,
        'starts_at' => now()->subDay(), 'reason' => 'Under investigation', 'created_by_id' => $this->officer->id,
    ]);
    expect(fn () => $this->issue->handle($this->record, $this->enrollment, 'ko', $this->officer))->toThrow(ValidationException::class, __('certificate.errors.hold'));
    $hold->forceFill(['ends_at' => now()->subMinute()])->save();
    expect($this->issue->handle($this->record, $this->enrollment, 'ko', $this->officer))->toBeInstanceOf(CertificateIssue::class);

    $clerk = User::factory()->withPermissions([Permission::StudentRecordsView])->create();
    expect(fn () => $this->issue->handle($this->record, $this->enrollment, 'ko', $clerk))->toThrow(AuthorizationException::class);

    expect(fn () => $this->issue->handle($this->record, $this->enrollment, 'fr', $this->officer))->toThrow(ValidationException::class);
});

it('verifies on the public page with masked holder details, and reports revoked and expired', function () {
    $issue = $this->issue->handle($this->record, $this->enrollment, 'ko', $this->officer);
    $name = $this->record->person->displayName('ko');

    $this->get(route('certificates.verify'))->assertOk()->assertSee(__('certificate.verify.intro'));
    $this->get(route('certificates.verify', $issue->verification_code))->assertOk()
        ->assertSee(__('certificate.verify.valid'))->assertSee($issue->serial_number)
        ->assertSee(CertificateVerification::maskName($name))->assertDontSee($this->record->student_number);
    $this->get(route('certificates.verify', 'NOPE'))->assertOk()->assertSee(__('certificate.verify.invalid_unknown'));

    $this->artisan('certificate:verify', ['code' => $issue->verification_code])->expectsOutputToContain('Valid.')->assertSuccessful();

    $this->issue->revoke($issue, $this->officer, 'Wrong purpose');
    expect($issue->refresh()->status)->toBe(CertificateIssueStatus::Revoked)->and($issue->revoked_at)->not->toBeNull();
    $this->get(route('certificates.verify', $issue->verification_code))->assertSee(__('certificate.verify.invalid_revoked'));
    $this->artisan('certificate:verify', ['code' => $issue->verification_code])->assertExitCode(2);
    expect(fn () => $this->issue->revoke($issue, $this->officer, 'Again'))->toThrow(ValidationException::class);

    $settings = app(CertificateSettings::class);
    $settings->verification_valid_days = 30;
    $settings->save();
    $old = $this->issue->handle($this->record, $this->enrollment, 'ko', $this->officer);
    $old->forceFill(['issued_at' => now()->subDays(31)])->save();
    $this->get(route('certificates.verify', $old->verification_code))->assertSee(__('certificate.verify.invalid_expired'));
});

it('renders a transcript with the finalized grades and renders again by command', function () {
    $term = Term::factory()->published()->between('2025-03-01', '2025-06-20')->create();
    Enrollment::factory()->inSection(Section::factory()->published()->inTerm($term)->create())->completed('A0')->create(['student_record_id' => $this->record->id]);

    $issue = $this->issue->handle($this->record, $this->transcript, 'en', $this->officer);
    $content = app(CertificateRenderer::class)->content($issue);
    expect($content->terms)->toHaveCount(1)->and($content->terms[0]['enrollments'])->toHaveCount(1)
        ->and($content->paragraphs)->not->toBeEmpty()->and($content->qrSvg)->toContain('<svg');

    $checksum = $issue->pdf_checksum;
    $this->artisan('certificate:render', ['issue' => $issue->serial_number])->assertFailed();
    $this->artisan('certificate:render', ['issue' => 'NOPE', '--reason' => 'x'])->assertFailed();
    $this->artisan('certificate:render', ['issue' => $issue->serial_number, '--reason' => 'Template fixed'])->assertSuccessful();
    expect($issue->refresh()->pdf_checksum)->toHaveLength(64)->and($issue->serial_number)->toBe('TRN-'.now()->year.'-000001')
        ->and(Activity::query()->where('event', 'certificate.rerendered')->exists())->toBeTrue();
    expect($checksum)->not->toBeNull();
});

it('issues from the student record page, lists the ledger, and lets the holder download', function () {
    Livewire::actingAs($this->officer)->test(ViewStudentRecord::class, ['record' => $this->record->getRouteKey()])
        ->callAction('issueCertificate', ['certificate_type_id' => $this->enrollment->id, 'locale' => 'ko', 'purpose' => 'Visa', 'copies' => 1])
        ->assertHasNoActionErrors();
    $issue = CertificateIssue::query()->sole();

    foreach ([CertificateIssueResource::getUrl('index'), CertificateTypeResource::getUrl('index'), CertificateTypeResource::getUrl('edit', ['record' => $this->enrollment]), ManageCertificateSettings::getUrl()] as $url) {
        $this->actingAs($this->officer)->get($url)->assertOk($url);
    }
    $student = User::factory()->create(['person_id' => $this->record->person_id]);
    $this->actingAs($student)->get(route('certificates.download', $issue))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    Livewire::actingAs($student)->test(MyCertificates::class)->assertSee($issue->serial_number);

    Livewire::actingAs($this->officer)->test(ListCertificateIssues::class)->assertCanSeeTableRecords([$issue])
        ->callTableAction('revoke', $issue, ['reason' => 'Mistake'])->assertHasNoTableActionErrors();
    expect($issue->refresh()->status)->toBe(CertificateIssueStatus::Revoked);

    // Staff keep the file for the record; the holder cannot download a revoked certificate.
    $this->actingAs($this->officer)->get(route('certificates.download', $issue))->assertOk();
    $this->actingAs($student)->get(route('certificates.download', $issue))->assertForbidden();

    $other = User::factory()->create(['person_id' => StudentRecord::factory()->create()->person_id]);
    $this->actingAs($other)->get(route('certificates.download', $issue))->assertForbidden();
    Livewire::actingAs($other)->test(MyCertificates::class)->assertDontSee($issue->serial_number);
});

it('hides other students\' issues at the database level', function () {
    $issue = $this->issue->handle($this->record, $this->enrollment, 'ko', $this->officer);
    $other = User::factory()->create(['person_id' => StudentRecord::factory()->create()->person_id]);
    $context = app(DatabaseSessionContext::class);

    $context->forUser($other->id);
    expect(CertificateIssue::query()->count())->toBe(0);

    $context->forUser(User::factory()->create(['person_id' => $this->record->person_id])->id);
    expect(CertificateIssue::query()->whereKey($issue->id)->exists())->toBeTrue();
    // RLS filters the row from the update: nothing changes, no error.
    expect(CertificateIssue::query()->whereKey($issue->id)->update(['purpose' => 'hacked']))->toBe(0);
    $context->forSystem('test');
    expect($issue->refresh()->purpose)->toBeNull();
});
