<?php

use App\Http\Controllers\Admission\AdmissionCycleListController;
use App\Http\Controllers\Admission\ApplicationDocumentController;
use App\Http\Controllers\Assessment\EvidenceController;
use App\Http\Controllers\Attendance\AttendanceSheetController;
use App\Http\Controllers\Attendance\RosterCsvController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\Auth\TwoFactorEmailChallengeController;
use App\Http\Controllers\Certificate\DownloadCertificateController;
use App\Http\Controllers\Certificate\VerifyCertificateController;
use App\Http\Controllers\Grading\TranscriptController;
use App\Http\Controllers\Person\PersonPhotoController;
use App\Http\Controllers\UpdateLocaleController;
use App\Livewire\Portal\Advisees;
use App\Livewire\Portal\ApplicationDetail;
use App\Livewire\Portal\Applications;
use App\Livewire\Portal\CourseCatalog;
use App\Livewire\Portal\Dashboard;
use App\Livewire\Portal\Evaluations;
use App\Livewire\Portal\GradeEntry;
use App\Livewire\Portal\MyAssessments;
use App\Livewire\Portal\MyAttendance;
use App\Livewire\Portal\MyCertificates;
use App\Livewire\Portal\MyGrades;
use App\Livewire\Portal\MyProfile;
use App\Livewire\Portal\MyRecords;
use App\Livewire\Portal\MyTeaching;
use App\Livewire\Portal\Registration;
use App\Livewire\Portal\SectionAttendance;
use App\Livewire\Portal\SectionDetail;
use App\Livewire\Portal\SectionEvaluation;
use App\Livewire\Portal\SecuritySettings;
use App\Livewire\Portal\SyllabusEditor;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/portal');

Route::get('/admissions', AdmissionCycleListController::class)->name('admissions.index');
Route::get('/verify/{code?}', VerifyCertificateController::class)->middleware('throttle:30,1')->name('certificates.verify');

Route::post('/locale', UpdateLocaleController::class)->name('locale.update');

Route::get('/auth/sso/{provider}/redirect', [SsoController::class, 'redirect'])->middleware('throttle:sso')->name('sso.redirect');
Route::get('/auth/sso/{provider}/callback', [SsoController::class, 'callback'])->middleware('throttle:sso')->name('sso.callback');

Route::get('/invitations/{token}', [InvitationController::class, 'show'])->middleware('guest')->name('invitations.show');
Route::post('/invitations/{token}', [InvitationController::class, 'store'])->middleware(['guest', 'throttle:6,1'])->name('invitations.store');

Route::middleware(['guest', 'throttle:two-factor'])->prefix('two-factor-challenge/email')->name('two-factor.email.')->group(function () {
    Route::post('/send', [TwoFactorEmailChallengeController::class, 'send'])->name('send');
    Route::post('/', [TwoFactorEmailChallengeController::class, 'store'])->name('store');
});

Route::get('/people/{person}/photo', PersonPhotoController::class)->middleware(['auth', 'mfa'])->name('people.photo');
Route::get('/sections/{section}/roster.csv', RosterCsvController::class)->middleware(['auth', 'mfa'])->name('sections.roster.csv');
Route::get('/sections/{section}/attendance-sheet', AttendanceSheetController::class)->middleware(['auth', 'mfa'])->name('sections.attendance.sheet');
Route::get('/assessment-results/{assessmentResult}/evidence', EvidenceController::class)->middleware(['auth', 'mfa'])->name('assessments.evidence');
Route::get('/certificates/{certificateIssue}/download', DownloadCertificateController::class)->middleware(['auth', 'mfa'])->name('certificates.download');
Route::get('/student-records/{studentRecord}/transcript', [TranscriptController::class, 'show'])->middleware(['auth', 'mfa'])->name('transcripts.show');
Route::get('/applications/{application}/documents/{media}', ApplicationDocumentController::class)->middleware(['auth', 'mfa'])->name('applications.documents.show');

Route::middleware(['auth', 'mfa'])->prefix('portal')->name('portal.')->group(function () {
    Route::livewire('/', Dashboard::class)->name('dashboard');
    Route::livewire('/security', SecuritySettings::class)->name('security');
    Route::livewire('/records', MyRecords::class)->name('records');
    Route::livewire('/profile', MyProfile::class)->name('profile');
    Route::livewire('/advisees', Advisees::class)->name('advisees');
    Route::livewire('/catalog', CourseCatalog::class)->name('catalog');
    Route::livewire('/registration', Registration::class)->name('registration');
    Route::livewire('/sections/{section}', SectionDetail::class)->name('sections.show');
    Route::livewire('/sections/{section}/syllabus', SyllabusEditor::class)->name('syllabus.edit');
    Route::livewire('/teaching', MyTeaching::class)->name('teaching');
    Route::livewire('/sections/{section}/grades', GradeEntry::class)->name('grades.entry');
    Route::livewire('/sections/{section}/attendance', SectionAttendance::class)->name('attendance.section');
    Route::livewire('/attendance', MyAttendance::class)->name('attendance');
    Route::livewire('/grades', MyGrades::class)->name('grades');
    Route::livewire('/evaluations', Evaluations::class)->name('evaluations');
    Route::livewire('/certificates', MyCertificates::class)->name('certificates');
    Route::livewire('/assessments', MyAssessments::class)->name('assessments');
    Route::livewire('/sections/{section}/evaluation', SectionEvaluation::class)->name('evaluations.results');
    Route::get('/grades/transcript', [TranscriptController::class, 'own'])->name('grades.transcript');

    // Applying needs a verified personal email (docs/porting-plan.md section 7.1).
    Route::middleware('verified')->group(function () {
        Route::livewire('/applications', Applications::class)->name('applications');
        Route::livewire('/applications/{application}', ApplicationDetail::class)->name('applications.show');
    });
});
