<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'phone:INTERNATIONAL'],
            'message' => ['required', 'string', 'max:2000'],
            // Honeypot: real visitors never fill this (hidden off-screen).
            // Never "required" — a bot must be free to fill it and pass validation.
            'website' => ['nullable', 'string'],
        ];
    }
}
