<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\PendingImageController;
use App\Http\Controllers\Dashboard\PropertyController;
use App\Http\Controllers\Dashboard\PropertyImageController;
use App\Http\Controllers\Dashboard\SettingsController;
use App\Http\Controllers\Site\ContactMessageController;
use App\Http\Controllers\Site\FavoriteController;
use App\Http\Controllers\Site\PropertyController as SitePropertyController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/properties', [SitePropertyController::class, 'index'])->name('properties.index');
Route::get('/properties/{property:slug}', [SitePropertyController::class, 'show'])->name('properties.show');
Route::post('/properties/{property:slug}/contact', [ContactMessageController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('properties.contact');

Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
Route::get('/favorites/properties', [FavoriteController::class, 'properties'])->name('favorites.properties');

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

    Route::post('/properties/{property}/images', [PropertyImageController::class, 'store'])
        ->name('dashboard.properties.images.store');
    Route::put('/properties/{property}/images/order', [PropertyImageController::class, 'reorder'])
        ->name('dashboard.properties.images.reorder');
    Route::put('/properties/{property}/images/{image}/primary', [PropertyImageController::class, 'makePrimary'])
        ->scopeBindings()
        ->name('dashboard.properties.images.primary');
    Route::delete('/properties/{property}/images/{image}', [PropertyImageController::class, 'destroy'])
        ->scopeBindings()
        ->name('dashboard.properties.images.destroy');

    // Create-form uploads: the property does not exist yet (FAZAT.md 4B).
    Route::post('/pending-images', [PendingImageController::class, 'store'])
        ->name('dashboard.pending-images.store');
    Route::delete('/pending-images/{id}', [PendingImageController::class, 'destroy'])
        ->name('dashboard.pending-images.destroy');

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
