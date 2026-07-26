<?php

use App\Models\Setting;
use Inertia\Testing\AssertableInertia as Assert;

test('the terms page renders the component with terms_content', function () {
    Setting::updateOrCreate(['key' => 'terms_content'], ['value' => [
        'sq' => 'Termat ekzistuese.',
        'en' => 'The existing terms.',
        'de' => 'Die vorhandenen Bedingungen.',
    ]]);

    $this->get('/terms')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Terms')
            ->where('content', 'Termat ekzistuese.'));
});

test('the terms page falls back to the sq terms_content when the locale translation is missing', function () {
    Setting::updateOrCreate(['key' => 'terms_content'], ['value' => ['sq' => 'Vetëm shqip.']]);

    $this->get('/en/terms')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Terms')
            ->where('content', 'Vetëm shqip.'));
});

test('the terms page renders with null content when nothing is configured', function () {
    $this->get('/terms')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Terms')
            ->where('content', null));
});

test('the privacy page renders the component with privacy_content', function () {
    Setting::updateOrCreate(['key' => 'privacy_content'], ['value' => [
        'sq' => 'Politika ekzistuese.',
        'en' => 'The existing policy.',
        'de' => 'Die vorhandene Richtlinie.',
    ]]);

    $this->get('/privacy')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Privacy')
            ->where('content', 'Politika ekzistuese.'));
});

test('the privacy page falls back to the sq privacy_content when the locale translation is missing', function () {
    Setting::updateOrCreate(['key' => 'privacy_content'], ['value' => ['sq' => 'Vetëm shqip.']]);

    $this->get('/de/privacy')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Privacy')
            ->where('content', 'Vetëm shqip.'));
});

test('the privacy page renders with null content when nothing is configured', function () {
    $this->get('/privacy')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Privacy')
            ->where('content', null));
});
