<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

abstract class TeamMemberRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'position' => ['required', 'array'],
            'position.sq' => ['required', 'string', 'max:255'],
            'position.en' => ['nullable', 'string', 'max:255'],
            'position.de' => ['nullable', 'string', 'max:255'],

            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'position.sq.required' => 'Pozicioni në shqip është i detyrueshëm.',
        ];
    }

    /**
     * The validated attributes ready for persistence: the photo upload is
     * excluded (handled separately by the service), and empty translations
     * are dropped so locale fallback works.
     *
     * @return array<string, mixed>
     */
    public function teamMemberData(): array
    {
        $data = $this->safe()->except(['photo']);

        $data['position'] = array_filter(
            $data['position'] ?? [],
            fn (?string $value): bool => $value !== null && $value !== ''
        );

        return $data;
    }
}
