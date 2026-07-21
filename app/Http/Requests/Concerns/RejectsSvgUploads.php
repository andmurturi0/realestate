<?php

namespace App\Http\Requests\Concerns;

use Closure;
use Illuminate\Http\UploadedFile;

trait RejectsSvgUploads
{
    /**
     * Belt-and-suspenders alongside the `mimetypes` rule: SVGs can carry
     * embedded scripts, so they're rejected explicitly with their own message
     * rather than relying solely on the mimetype allowlist.
     */
    protected function rejectSvg(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof UploadedFile
            && in_array($value->getMimeType(), ['image/svg+xml', 'image/svg'], true)) {
            $fail('Fotot SVG nuk lejohen.');
        }
    }
}
