<?php

namespace App\Policies;

use App\Models\User;

class SettingsPolicy
{
    /**
     * Determine whether the user can view and update agency settings.
     *
     * Settings is a key/value singleton, not a per-instance resource, so a
     * single ability covers the whole surface (branding, contact, social,
     * content).
     */
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }
}
