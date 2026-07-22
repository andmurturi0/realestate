<?php

namespace App\Http\Requests\Dashboard;

use App\Models\User;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends UserRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
