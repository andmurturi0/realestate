<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\Inbox\MessageController as InboxMessageController;
use App\Http\Controllers\Dashboard\Inbox\OfferController as InboxOfferController;
use App\Http\Controllers\Dashboard\Inbox\RequestController as InboxRequestController;
use App\Http\Controllers\Dashboard\PendingImageController;
use App\Http\Controllers\Dashboard\PropertyController;
use App\Http\Controllers\Dashboard\PropertyImageController;
use App\Http\Controllers\Dashboard\SettingsController;
use App\Http\Controllers\Dashboard\TeamMemberController;
use App\Http\Controllers\Dashboard\TestimonialController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Site\AboutController;
use App\Http\Controllers\Site\ContactMessageController;
use App\Http\Controllers\Site\FavoriteController;
use App\Http\Controllers\Site\LegalController;
use App\Http\Controllers\Site\PropertyController as SitePropertyController;
use App\Http\Controllers\Site\PropertyOfferController;
use App\Http\Controllers\Site\PropertyRequestController;
use App\Http\Controllers\SitemapController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/robots.txt', function () {
    return response("User-agent: *\nDisallow: /dashboard\nDisallow: /login\nSitemap: ".url('/sitemap.xml')."\n")
        ->header('Content-Type', 'text/plain');
})->name('robots');

// The public "pages" — every one of these is mirrored below under /en and
// /de. sq stays unprefixed (FAZAT.md Faza 8), so this closure is registered
// twice: once as-is for sq, once inside a {locale} prefix + "locale." name
// prefix for en/de. Keep this closure to GET page routes only — form/action
// routes (contact, offer, request) don't need a locale-prefixed URL.
$publicPages = function () {
    Route::get('/', [SitePropertyController::class, 'index'])->name('home');

    Route::get('/properties', [SitePropertyController::class, 'index'])->name('properties.index');
    Route::get('/properties/{property:slug}', [SitePropertyController::class, 'show'])->name('properties.show');
    Route::get('/offer-property', [PropertyOfferController::class, 'create'])->name('offer-property.create');
    Route::get('/create-request', [PropertyRequestController::class, 'create'])->name('create-request.create');
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::get('/about', [AboutController::class, 'show'])->name('about');
    Route::get('/terms', [LegalController::class, 'terms'])->name('terms');
    Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');
};

Route::middleware(SetLocale::class)->group(function () use ($publicPages) {
    $publicPages();

    Route::post('/properties/{property:slug}/contact', [ContactMessageController::class, 'store'])
        ->middleware('throttle:contact')
        ->name('properties.contact');

    Route::post('/offer-property', [PropertyOfferController::class, 'store'])
        ->middleware('throttle:offer')
        ->name('offer-property.store');

    Route::post('/create-request', [PropertyRequestController::class, 'store'])
        ->middleware('throttle:request')
        ->name('create-request.store');

    Route::get('/favorites/properties', [FavoriteController::class, 'properties'])->name('favorites.properties');
});

Route::prefix('{locale}')
    ->where(['locale' => 'en|de'])
    ->middleware(SetLocale::class)
    ->name('locale.')
    ->group($publicPages);

Route::middleware(['auth', 'verified', 'active'])->prefix('dashboard')->group(function () {
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

    Route::get('/inbox/messages', [InboxMessageController::class, 'index'])
        ->name('dashboard.inbox.messages');
    Route::patch('/inbox/messages/{message}/status', [InboxMessageController::class, 'updateStatus'])
        ->name('dashboard.inbox.messages.status');
    Route::patch('/inbox/messages/{message}/assign', [InboxMessageController::class, 'assign'])
        ->name('dashboard.inbox.messages.assign');
    Route::post('/inbox/messages/{message}/notes', [InboxMessageController::class, 'storeNote'])
        ->name('dashboard.inbox.messages.notes.store');

    Route::get('/inbox/offers', [InboxOfferController::class, 'index'])
        ->name('dashboard.inbox.offers');
    Route::patch('/inbox/offers/{offer}/status', [InboxOfferController::class, 'updateStatus'])
        ->name('dashboard.inbox.offers.status');
    Route::patch('/inbox/offers/{offer}/assign', [InboxOfferController::class, 'assign'])
        ->name('dashboard.inbox.offers.assign');
    Route::post('/inbox/offers/{offer}/notes', [InboxOfferController::class, 'storeNote'])
        ->name('dashboard.inbox.offers.notes.store');
    Route::get('/inbox/offers/{offer}/convert', [InboxOfferController::class, 'createFromOffer'])
        ->name('dashboard.inbox.offers.convert.create');
    Route::post('/inbox/offers/{offer}/convert', [InboxOfferController::class, 'convert'])
        ->name('dashboard.inbox.offers.convert.store');

    Route::get('/inbox/requests', [InboxRequestController::class, 'index'])
        ->name('dashboard.inbox.requests');
    Route::patch('/inbox/requests/{propertyRequest}/status', [InboxRequestController::class, 'updateStatus'])
        ->name('dashboard.inbox.requests.status');
    Route::patch('/inbox/requests/{propertyRequest}/assign', [InboxRequestController::class, 'assign'])
        ->name('dashboard.inbox.requests.assign');
    Route::post('/inbox/requests/{propertyRequest}/notes', [InboxRequestController::class, 'storeNote'])
        ->name('dashboard.inbox.requests.notes.store');

    Route::middleware('admin')->group(function () {
        Route::get('/agents', [UserController::class, 'index'])
            ->name('dashboard.agents.index');
        Route::get('/agents/create', [UserController::class, 'create'])
            ->name('dashboard.agents.create');
        Route::post('/agents', [UserController::class, 'store'])
            ->name('dashboard.agents.store');
        Route::get('/agents/{user}/edit', [UserController::class, 'edit'])
            ->name('dashboard.agents.edit');
        Route::put('/agents/{user}', [UserController::class, 'update'])
            ->name('dashboard.agents.update');
        Route::patch('/agents/{user}/toggle-active', [UserController::class, 'toggleActive'])
            ->name('dashboard.agents.toggle-active');
        Route::delete('/agents/{user}', [UserController::class, 'destroy'])
            ->name('dashboard.agents.destroy');

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

        Route::get('/testimonials', [TestimonialController::class, 'index'])
            ->name('dashboard.testimonials.index');
        Route::get('/testimonials/create', [TestimonialController::class, 'create'])
            ->name('dashboard.testimonials.create');
        Route::post('/testimonials', [TestimonialController::class, 'store'])
            ->name('dashboard.testimonials.store');
        Route::put('/testimonials/order', [TestimonialController::class, 'reorder'])
            ->name('dashboard.testimonials.reorder');
        Route::get('/testimonials/{testimonial}/edit', [TestimonialController::class, 'edit'])
            ->name('dashboard.testimonials.edit');
        Route::put('/testimonials/{testimonial}', [TestimonialController::class, 'update'])
            ->name('dashboard.testimonials.update');
        Route::delete('/testimonials/{testimonial}', [TestimonialController::class, 'destroy'])
            ->name('dashboard.testimonials.destroy');

        Route::get('/team-members', [TeamMemberController::class, 'index'])
            ->name('dashboard.team-members.index');
        Route::get('/team-members/create', [TeamMemberController::class, 'create'])
            ->name('dashboard.team-members.create');
        Route::post('/team-members', [TeamMemberController::class, 'store'])
            ->name('dashboard.team-members.store');
        Route::put('/team-members/order', [TeamMemberController::class, 'reorder'])
            ->name('dashboard.team-members.reorder');
        Route::get('/team-members/{teamMember}/edit', [TeamMemberController::class, 'edit'])
            ->name('dashboard.team-members.edit');
        Route::put('/team-members/{teamMember}', [TeamMemberController::class, 'update'])
            ->name('dashboard.team-members.update');
        Route::delete('/team-members/{teamMember}', [TeamMemberController::class, 'destroy'])
            ->name('dashboard.team-members.destroy');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
