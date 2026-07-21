<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\Concerns\RejectsSvgUploads;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingSettingsRequest extends FormRequest
{
    use RejectsSvgUploads;

    public function authorize(): bool
    {
        return $this->user()?->can('manage', Setting::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'agency_name' => ['required', 'string', 'max:120'],
            'primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'watermark_enabled' => ['required', 'boolean'],
            'logo' => ['nullable', 'file', $this->rejectSvg(...), 'mimetypes:image/jpeg,image/png,image/webp', 'max:4096'],
            'logo_dark' => ['nullable', 'file', $this->rejectSvg(...), 'mimetypes:image/jpeg,image/png,image/webp', 'max:4096'],
            'favicon' => ['nullable', 'file', $this->rejectSvg(...), 'mimetypes:image/jpeg,image/png,image/webp', 'max:1024'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.mimetypes' => 'Lejohen vetëm fotot JPEG, PNG ose WebP.',
            'logo_dark.mimetypes' => 'Lejohen vetëm fotot JPEG, PNG ose WebP.',
            'favicon.mimetypes' => 'Lejohen vetëm fotot JPEG, PNG ose WebP.',
        ];
    }
}
