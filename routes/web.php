<?php

use App\Http\Controllers\Admission\AdmissionCycleListController;
use App\Http\Controllers\Admission\ApplicationDocumentController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\Auth\TwoFactorEmailChallengeController;
use App\Http\Controllers\Person\PersonPhotoController;
use App\Http\Controllers\UpdateLocaleController;
use App\Livewire\Portal\Advisees;
use App\Livewire\Portal\ApplicationDetail;
use App\Livewire\Portal\Applications;
use App\Livewire\Portal\Dashboard;
use App\Livewire\Portal\MyProfile;
use App\Livewire\Portal\MyRecords;
use App\Livewire\Portal\SecuritySettings;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/portal');

Route::get('/admissions', AdmissionCycleListController::class)->name('admissions.index');

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
Route::get('/applications/{application}/documents/{media}', ApplicationDocumentController::class)->middleware(['auth', 'mfa'])->name('applications.documents.show');

Route::middleware(['auth', 'mfa'])->prefix('portal')->name('portal.')->group(function () {
    Route::livewire('/', Dashboard::class)->name('dashboard');
    Route::livewire('/security', SecuritySettings::class)->name('security');
    Route::livewire('/records', MyRecords::class)->name('records');
    Route::livewire('/profile', MyProfile::class)->name('profile');
    Route::livewire('/advisees', Advisees::class)->name('advisees');

    // Applying needs a verified personal email (docs/porting-plan.md section 7.1).
    Route::middleware('verified')->group(function () {
        Route::livewire('/applications', Applications::class)->name('applications');
        Route::livewire('/applications/{application}', ApplicationDetail::class)->name('applications.show');
    });
});
