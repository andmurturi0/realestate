<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'admin'])->get('/_test/admin-only', fn () => 'ok');
});

// Cilësimet / zonat vetëm-admin: mbrohen me middleware-in 'admin'

test('an admin can access admin-only routes', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/_test/admin-only')->assertSuccessful();
});

test('an agent is forbidden from admin-only routes', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)->get('/_test/admin-only')->assertForbidden();
});

test('a guest is redirected to login from admin-only routes', function () {
    $this->get('/_test/admin-only')->assertRedirect(route('login'));
});
