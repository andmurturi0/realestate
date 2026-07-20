<?php

namespace App\Policies;

use App\Models\TeamMember;
use App\Models\User;

class TeamMemberPolicy
{
    /**
     * Determine whether the user can view the team member list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the team member.
     */
    public function view(User $user, TeamMember $teamMember): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create team members.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the team member.
     */
    public function update(User $user, TeamMember $teamMember): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the team member.
     */
    public function delete(User $user, TeamMember $teamMember): bool
    {
        return $user->isAdmin();
    }
}
