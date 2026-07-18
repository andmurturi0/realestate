<?php

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\PropertyOffer;
use App\Models\PropertyRequest;
use App\Models\User;

/**
 * Shared policy for the three inbox lead types:
 * ContactMessage, PropertyOffer, PropertyRequest.
 */
class LeadPolicy
{
    /**
     * Determine whether the user can view the inbox.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the lead.
     */
    public function view(User $user, ContactMessage|PropertyOffer|PropertyRequest $lead): bool
    {
        return $user->isAdmin() || $lead->assigned_to === $user->id;
    }

    /**
     * Determine whether the user can update the lead (status, notes).
     */
    public function update(User $user, ContactMessage|PropertyOffer|PropertyRequest $lead): bool
    {
        return $user->isAdmin() || $lead->assigned_to === $user->id;
    }

    /**
     * Determine whether the user can assign the lead to an agent.
     */
    public function assign(User $user, ContactMessage|PropertyOffer|PropertyRequest $lead): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the lead.
     */
    public function delete(User $user, ContactMessage|PropertyOffer|PropertyRequest $lead): bool
    {
        return $user->isAdmin();
    }
}
