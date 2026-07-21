<?php

use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Inertia\Testing\AssertableInertia as Assert;

test('the about page renders the component with about_content, testimonials and team', function () {
    Setting::updateOrCreate(['key' => 'about_content'], ['value' => [
        'sq' => 'Teksti ekzistues rreth agjencisë.',
        'en' => 'The existing about text.',
        'de' => 'Der vorhandene Über-uns-Text.',
    ]]);
    $testimonial = Testimonial::factory()->create(['author_name' => 'Klient Aktiv']);
    $teamMember = TeamMember::factory()->create(['name' => 'Anëtar Aktiv']);

    $this->get('/about')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('About')
            ->where('about_content', 'Teksti ekzistues rreth agjencisë.')
            ->has('testimonials', 1)
            ->where('testimonials.0.id', $testimonial->id)
            ->where('testimonials.0.author_name', 'Klient Aktiv')
            ->has('teamMembers', 1)
            ->where('teamMembers.0.id', $teamMember->id)
            ->where('teamMembers.0.name', 'Anëtar Aktiv'));
});

test('inactive testimonials and team members never appear on the about page', function () {
    Testimonial::factory()->inactive()->create();
    TeamMember::factory()->inactive()->create();

    $this->get('/about')
        ->assertInertia(fn (Assert $page) => $page
            ->has('testimonials', 0)
            ->has('teamMembers', 0));
});

test('testimonials and team members are ordered by sort_order', function () {
    $last = Testimonial::factory()->create(['sort_order' => 2, 'author_name' => 'I fundit']);
    $first = Testimonial::factory()->create(['sort_order' => 0, 'author_name' => 'I pari']);

    $this->get('/about')
        ->assertInertia(fn (Assert $page) => $page
            ->where('testimonials.0.id', $first->id)
            ->where('testimonials.1.id', $last->id));
});

test('the about page falls back to the sq about_content when the locale translation is missing', function () {
    Setting::updateOrCreate(['key' => 'about_content'], ['value' => ['sq' => 'Vetëm shqip.']]);

    $this->get('/en/about')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('About')
            ->where('about_content', 'Vetëm shqip.'));
});

test('a testimonial quote falls back to sq under a locale without a translation', function () {
    Testimonial::factory()->create(['quote' => ['sq' => 'Vetëm shqip citim.']]);

    $this->get('/de/about')
        ->assertInertia(fn (Assert $page) => $page
            ->where('testimonials.0.quote', 'Vetëm shqip citim.'));
});
