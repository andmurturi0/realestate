<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends UserRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $target = $this->route('user');

            if ($target !== null
                && $this->user() !== null
                && $target->id === $this->user()->id
                && ! $this->boolean('is_active')) {
                $validator->errors()->add('is_active', 'Nuk mund ta çaktivizosh llogarinë tënde.');
            }
        });
    }
}
