<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\UserRole;
use App\Http\Requests\Concerns\RejectsSvgUploads;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class UserRequest extends FormRequest
{
    use RejectsSvgUploads;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::enum(UserRole::class)],

            'bio' => ['nullable', 'array'],
            'bio.sq' => ['nullable', 'string', 'max:2000'],
            'bio.en' => ['nullable', 'string', 'max:2000'],
            'bio.de' => ['nullable', 'string', 'max:2000'],

            'avatar' => ['nullable', 'file', $this->rejectSvg(...), 'mimetypes:image/jpeg,image/png,image/webp', 'max:4096'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.mimetypes' => 'Lejohen vetëm fotot JPEG, PNG ose WebP.',
        ];
    }

    /**
     * The validated attributes ready for persistence: the avatar upload is
     * excluded (handled separately by the service), empty bio translations
     * are dropped so locale fallback works, and a blank password is dropped
     * so an update never overwrites an existing password with nothing.
     *
     * @return array<string, mixed>
     */
    public function userData(): array
    {
        $data = $this->safe()->except(['avatar']);

        if (array_key_exists('bio', $data)) {
            $data['bio'] = array_filter(
                $data['bio'] ?? [],
                fn (?string $value): bool => $value !== null && $value !== ''
            );
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}
