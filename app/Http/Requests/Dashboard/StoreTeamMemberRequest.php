<?php

namespace App\Http\Requests\Dashboard;

use App\Models\TeamMember;

class StoreTeamMemberRequest extends TeamMemberRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TeamMember::class) ?? false;
    }
}
