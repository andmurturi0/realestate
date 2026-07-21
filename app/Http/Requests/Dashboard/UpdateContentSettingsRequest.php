<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContentSettingsRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    private const CONTENT_KEYS = ['about_content', 'terms_content', 'privacy_content', 'faq_content'];

    public function authorize(): bool
    {
        return $this->user()?->can('manage', Setting::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (self::CONTENT_KEYS as $key) {
            $rules[$key] = ['required', 'array'];

            foreach (['sq', 'en', 'de'] as $locale) {
                $rules["{$key}.{$locale}"] = ['nullable', 'string'];
            }
        }

        return $rules;
    }
}
