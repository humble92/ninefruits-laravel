<?php

use App\Http\Controllers\Auth\TwoFactorEmailChallengeController;
use App\Http\Controllers\UpdateLocaleController;
use App\Livewire\Portal\Dashboard;
use App\Livewire\Portal\SecuritySettings;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/portal');

Route::post('/locale', UpdateLocaleController::class)->name('locale.update');

Route::middleware(['guest', 'throttle:two-factor'])->prefix('two-factor-challenge/email')->name('two-factor.email.')->group(function () {
    Route::post('/send', [TwoFactorEmailChallengeController::class, 'send'])->name('send');
    Route::post('/', [TwoFactorEmailChallengeController::class, 'store'])->name('store');
});

Route::middleware(['auth', 'mfa'])->prefix('portal')->name('portal.')->group(function () {
    Route::livewire('/', Dashboard::class)->name('dashboard');
    Route::livewire('/security', SecuritySettings::class)->name('security');
});
