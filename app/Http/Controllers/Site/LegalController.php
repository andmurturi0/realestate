<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    public function terms(): Response
    {
        return $this->renderLegalPage('Terms', 'terms_content');
    }

    public function privacy(): Response
    {
        return $this->renderLegalPage('Privacy', 'privacy_content');
    }

    private function renderLegalPage(string $component, string $settingKey): Response
    {
        $content = Setting::get($settingKey);

        return Inertia::render($component, [
            'content' => is_array($content) ? ($content[app()->getLocale()] ?? $content['sq'] ?? null) : null,
        ]);
    }
}
