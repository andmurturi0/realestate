<?php

namespace App\Http\Requests\Dashboard;

use App\Services\ImageService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

abstract class PropertyImageUploadRequest extends FormRequest
{
    /**
     * How many images the target already has — uploads beyond
     * ImageService::MAX_PER_PROPERTY are rejected.
     */
    abstract protected function currentImageCount(): int;

    /**
     * The mimetypes rule inspects the actual file content (never the
     * client extension), so a PHP script renamed to .jpg fails here.
     * SVG is additionally blocked by an explicit rule with its own
     * message.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                $this->rejectSvg(...),
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:8192',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.mimetypes' => 'Lejohen vetëm fotot JPEG, PNG ose WebP.',
            'image.max' => 'Fotoja nuk mund të jetë më e madhe se 8MB.',
        ];
    }

    /**
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->currentImageCount() >= ImageService::MAX_PER_PROPERTY) {
                    $validator->errors()->add(
                        'image',
                        'Një pronë mund të ketë më së shumti '.ImageService::MAX_PER_PROPERTY.' foto.'
                    );
                }
            },
        ];
    }

    protected function rejectSvg(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof UploadedFile
            && in_array($value->getMimeType(), ['image/svg+xml', 'image/svg'], true)) {
            $fail('Fotot SVG nuk lejohen.');
        }
    }
}
