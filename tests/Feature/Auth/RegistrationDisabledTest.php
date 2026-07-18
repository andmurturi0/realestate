<?php

use Illuminate\Support\Facades\Route;

test('the registration page returns 404', function () {
    $this->get('/register')->assertNotFound();
});

test('posting to the registration endpoint returns 404', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});

test('no register route is defined', function () {
    expect(Route::has('register'))->toBeFalse();
});
