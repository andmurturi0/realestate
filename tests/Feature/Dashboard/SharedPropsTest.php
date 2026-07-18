<?php

use App\Enums\LeadStatus;
use App\Enums\OfferStatus;
use App\Models\ContactMessage;
use App\Models\PropertyOffer;
use App\Models\PropertyRequest;
use App\Models\Setting;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// Settings si shared prop — nga cache, në çdo faqe

test('settings are shared with every inertia page', function () {
    Setting::create(['key' => 'agency_name', 'value' => 'Agjencia Ime']);
    Setting::create(['key' => 'primary_color', 'value' => '#123456']);

    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('settings.agency_name', 'Agjencia Ime')
            ->where('settings.primary_color', '#123456'));
});

test('heavy content settings are not shared with every page', function () {
    Setting::create(['key' => 'about_content', 'value' => ['sq' => 'tekst i gjatë']]);

    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page->missing('settings.about_content'));
});

// Fshehja e linqeve në sidebar bëhet me flamuj të llogaritur në server (@can)

test('permission flags are shared for an admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->where('can.manageAgents', true)
            ->where('can.manageSettings', true));
});

test('permission flags are shared as false for an agent', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->where('can.manageAgents', false)
            ->where('can.manageSettings', false));
});

// Badge-at e Inbox-it: një query për burim, të skopuara sipas rolit

test('badge counts for an admin count every new lead', function () {
    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create();

    ContactMessage::factory()->count(2)->create();
    ContactMessage::factory()->create(['assigned_to' => $agent->id]);
    ContactMessage::factory()->create(['status' => LeadStatus::Contacted]);

    PropertyOffer::factory()->create();
    PropertyOffer::factory()->create(['status' => OfferStatus::Rejected]);

    PropertyRequest::factory()->count(2)->create(['assigned_to' => $agent->id]);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->where('badges.messages', 3)
            ->where('badges.offers', 1)
            ->where('badges.requests', 2));
});

test('badge counts for an agent only count leads assigned to them', function () {
    $agent = User::factory()->agent()->create();
    $other = User::factory()->agent()->create();

    ContactMessage::factory()->create(['assigned_to' => $agent->id]);
    ContactMessage::factory()->create(['assigned_to' => $agent->id, 'status' => LeadStatus::Closed]);
    ContactMessage::factory()->count(2)->create(['assigned_to' => $other->id]);
    ContactMessage::factory()->count(2)->create();

    PropertyOffer::factory()->count(3)->create();

    PropertyRequest::factory()->create(['assigned_to' => $agent->id]);

    $this->actingAs($agent)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->where('badges.messages', 1)
            ->where('badges.offers', 0)
            ->where('badges.requests', 1));
});

test('guests get no badges', function () {
    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page->where('badges', null));
});
