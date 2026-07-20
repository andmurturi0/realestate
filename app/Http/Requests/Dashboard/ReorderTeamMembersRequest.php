<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderTeamMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'distinct', Rule::exists('team_members', 'id')],
        ];
    }

    /**
     * @return list<int>
     */
    public function orderedIds(): array
    {
        return array_map(intval(...), $this->validated('order'));
    }
}
