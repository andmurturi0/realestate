<?php

namespace App\Http\Requests\Dashboard;

class UpdateTeamMemberRequest extends TeamMemberRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('teamMember')) ?? false;
    }
}
