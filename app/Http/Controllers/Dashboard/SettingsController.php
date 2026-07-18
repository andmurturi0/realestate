<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateBrandingSettingsRequest;
use App\Http\Requests\Dashboard\UpdateContactSettingsRequest;
use App\Http\Requests\Dashboard\UpdateContentSettingsRequest;
use App\Http\Requests\Dashboard\UpdateSocialSettingsRequest;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settingsService) {}

    public function edit(): Response
    {
        return Inertia::render('dashboard/Settings', [
            'settings' => Setting::allAsArray(),
        ]);
    }

    public function updateBranding(UpdateBrandingSettingsRequest $request): RedirectResponse
    {
        $values = $request->safe()->except(['logo', 'logo_dark', 'favicon']);
        $values['watermark_enabled'] = $request->boolean('watermark_enabled');

        $this->settingsService->update($values);

        $imageInputs = [
            'logo' => 'logo_path',
            'logo_dark' => 'logo_dark_path',
            'favicon' => 'favicon_path',
        ];

        foreach ($imageInputs as $input => $key) {
            if ($request->hasFile($input)) {
                $this->settingsService->storeBrandingImage($key, $request->file($input));
            }
        }

        return back()->with('success', 'Cilësimet e brendit u ruajtën.');
    }

    public function updateContact(UpdateContactSettingsRequest $request): RedirectResponse
    {
        $this->settingsService->update($request->validated());

        return back()->with('success', 'Të dhënat e kontaktit u ruajtën.');
    }

    public function updateSocial(UpdateSocialSettingsRequest $request): RedirectResponse
    {
        $this->settingsService->update($request->validated());

        return back()->with('success', 'Rrjetet sociale u ruajtën.');
    }

    public function updateContent(UpdateContentSettingsRequest $request): RedirectResponse
    {
        $this->settingsService->update($request->validated());

        return back()->with('success', 'Përmbajtja u ruajt.');
    }
}
