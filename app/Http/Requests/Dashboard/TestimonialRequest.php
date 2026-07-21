<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\Concerns\RejectsSvgUploads;
use Illuminate\Foundation\Http\FormRequest;

abstract class TestimonialRequest extends FormRequest
{
    use RejectsSvgUploads;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'author_name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],

            'quote' => ['required', 'array'],
            'quote.sq' => ['required', 'string', 'max:2000'],
            'quote.en' => ['nullable', 'string', 'max:2000'],
            'quote.de' => ['nullable', 'string', 'max:2000'],

            'photo' => ['nullable', 'file', $this->rejectSvg(...), 'mimetypes:image/jpeg,image/png,image/webp', 'max:4096'],
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
            'quote.sq.required' => 'Citimi në shqip është i detyrueshëm.',
            'photo.mimetypes' => 'Lejohen vetëm fotot JPEG, PNG ose WebP.',
        ];
    }

    /**
     * The validated attributes ready for persistence: the photo upload is
     * excluded (handled separately by the service), and empty translations
     * are dropped so locale fallback works.
     *
     * @return array<string, mixed>
     */
    public function testimonialData(): array
    {
        $data = $this->safe()->except(['photo']);

        $data['quote'] = array_filter(
            $data['quote'] ?? [],
            fn (?string $value): bool => $value !== null && $value !== ''
        );

        return $data;
    }
}
