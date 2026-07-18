<?php

use App\Enums\LeadStatus;
use App\Models\ContactMessage;
use App\Models\Property;
use App\Models\PropertyOffer;
use App\Models\PropertyRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

// Kartat e statistikave — të skopuara sipas rolit

test('admin stats cover the whole agency', function () {
    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create();

    Property::factory()->count(3)->published()->create(['agent_id' => $agent->id]);
    Property::factory()->count(2)->draft()->create(['agent_id' => $agent->id]);

    ContactMessage::factory()->count(2)->create();
    ContactMessage::factory()->create(['status' => LeadStatus::Closed]);
    PropertyOffer::factory()->create(['created_at' => now()->subMonths(2)]);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.properties', 5)
            ->where('stats.published', 3)
            ->where('stats.newLeads', 3)
            ->where('stats.monthLeads', 3));
});

test('agent stats are scoped to their own properties and leads', function () {
    $agent = User::factory()->agent()->create();
    $other = User::factory()->agent()->create();

    Property::factory()->published()->create(['agent_id' => $agent->id]);
    Property::factory()->draft()->create(['agent_id' => $agent->id]);
    Property::factory()->count(3)->published()->create(['agent_id' => $other->id]);

    ContactMessage::factory()->create(['assigned_to' => $agent->id]);
    ContactMessage::factory()->count(4)->create(['assigned_to' => $other->id]);
    PropertyRequest::factory()->create(['assigned_to' => $agent->id, 'created_at' => now()->subMonths(3)]);

    $this->actingAs($agent)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.properties', 2)
            ->where('stats.published', 1)
            ->where('stats.newLeads', 2)
            ->where('stats.monthLeads', 1));
});

// Leads e fundit

test('the overview lists the five most recent leads across all sources', function () {
    $admin = User::factory()->admin()->create();

    ContactMessage::factory()->count(3)->create(['created_at' => now()->subDays(5)]);
    PropertyOffer::factory()->count(2)->create(['created_at' => now()->subDays(1)]);
    PropertyRequest::factory()->count(2)->create(['created_at' => now()->subHours(2)]);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->has('recentLeads', 5)
            ->where('recentLeads.0.type', 'request')
            ->where('recentLeads.2.type', 'offer'));
});

test('an agent only sees their own recent leads', function () {
    $agent = User::factory()->agent()->create();
    $other = User::factory()->agent()->create();

    ContactMessage::factory()->create(['assigned_to' => $agent->id, 'full_name' => 'Lead i Imi']);
    ContactMessage::factory()->count(4)->create(['assigned_to' => $other->id]);

    $this->actingAs($agent)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->has('recentLeads', 1)
            ->where('recentLeads.0.name', 'Lead i Imi'));
});

// Rregulli kritik i performancës: faqja e dashboard-it nën 15 query

test('the dashboard page stays under 15 queries', function () {
    $admin = User::factory()->admin()->create();

    Property::factory()->count(3)->published()->create();
    ContactMessage::factory()->count(3)->create();
    PropertyOffer::factory()->count(3)->create();
    PropertyRequest::factory()->count(3)->create();

    $queries = 0;
    DB::listen(function () use (&$queries) {
        $queries++;
    });

    $this->actingAs($admin)->get('/dashboard')->assertSuccessful();

    expect($queries)->toBeLessThan(15);
});
