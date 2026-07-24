<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController as FortifyTwoFactorAuthenticationController;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'edit'])->name('two-factor.edit');

    // Enabling/disabling/regenerating recovery codes all require a recently
    // confirmed password (Faza: fortify.features 'confirmPassword' option),
    // same gate Fortify's own routes use for these endpoints. The three
    // controllers below are Fortify's, reused unchanged — they only touch
    // the two-factor columns via its Actions, nothing app-specific.
    Route::middleware('password.confirm')->group(function () {
        Route::post('settings/two-factor', [FortifyTwoFactorAuthenticationController::class, 'store'])->name('two-factor.enable');
        Route::post('settings/two-factor/confirm', [ConfirmedTwoFactorAuthenticationController::class, 'store'])->name('two-factor.confirm');
        Route::delete('settings/two-factor', [FortifyTwoFactorAuthenticationController::class, 'destroy'])->name('two-factor.disable');
        Route::post('settings/two-factor/recovery-codes', [RecoveryCodeController::class, 'store'])->name('two-factor.recovery-codes.regenerate');
    });
});
