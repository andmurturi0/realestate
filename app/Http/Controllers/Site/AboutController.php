<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Inertia\Inertia;
use Inertia\Response;

class AboutController extends Controller
{
    public function show(): Response
    {
        $aboutContent = Setting::get('about_content');

        return Inertia::render('About', [
            'about_content' => is_array($aboutContent) ? ($aboutContent[app()->getLocale()] ?? $aboutContent['sq'] ?? null) : null,
            'testimonials' => Testimonial::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (Testimonial $testimonial): array => [
                    'id' => $testimonial->id,
                    'author_name' => $testimonial->author_name,
                    'company' => $testimonial->company,
                    'quote' => $testimonial->getTranslation('quote', app()->getLocale()),
                    'photo_url' => $testimonial->photo_url,
                ])
                ->values(),
            'teamMembers' => TeamMember::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (TeamMember $teamMember): array => [
                    'id' => $teamMember->id,
                    'name' => $teamMember->name,
                    'position' => $teamMember->getTranslation('position', app()->getLocale()),
                    'photo_url' => $teamMember->photo_url,
                ])
                ->values(),
        ]);
    }
}
