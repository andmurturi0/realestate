<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\PropertyController;
use App\Http\Controllers\Dashboard\SettingsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/properties', [PropertyController::class, 'index'])
        ->name('dashboard.properties.index');
    Route::get('/properties/create', [PropertyController::class, 'create'])
        ->name('dashboard.properties.create');
    Route::post('/properties', [PropertyController::class, 'store'])
        ->name('dashboard.properties.store');
    Route::get('/properties/{property}/edit', [PropertyController::class, 'edit'])
        ->name('dashboard.properties.edit');
    Route::put('/properties/{property}', [PropertyController::class, 'update'])
        ->name('dashboard.properties.update');
    Route::delete('/properties/{property}', [PropertyController::class, 'destroy'])
        ->name('dashboard.properties.destroy');

    // Placeholder shells — the real pages arrive in later phases (FAZAT.md).
    Route::inertia('/inbox/messages', 'dashboard/Placeholder', ['title' => 'Mesazhet'])
        ->name('dashboard.inbox.messages');
    Route::inertia('/inbox/offers', 'dashboard/Placeholder', ['title' => 'Ofertat'])
        ->name('dashboard.inbox.offers');
    Route::inertia('/inbox/requests', 'dashboard/Placeholder', ['title' => 'Kërkesat'])
        ->name('dashboard.inbox.requests');

    Route::middleware('admin')->group(function () {
        Route::inertia('/agents', 'dashboard/Placeholder', ['title' => 'Agjentët'])
            ->name('dashboard.agents.index');

        Route::get('/settings', [SettingsController::class, 'edit'])
            ->name('dashboard.settings.edit');
        Route::post('/settings/branding', [SettingsController::class, 'updateBranding'])
            ->name('dashboard.settings.branding');
        Route::put('/settings/contact', [SettingsController::class, 'updateContact'])
            ->name('dashboard.settings.contact');
        Route::put('/settings/social', [SettingsController::class, 'updateSocial'])
            ->name('dashboard.settings.social');
        Route::put('/settings/content', [SettingsController::class, 'updateContent'])
            ->name('dashboard.settings.content');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
