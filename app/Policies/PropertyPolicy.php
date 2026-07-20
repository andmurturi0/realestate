<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    /**
     * Determine whether the user can view any properties.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the property.
     */
    public function view(User $user, Property $property): bool
    {
        return true;
    }

    /**
     * Determine whether the user may view a non-published property through
     * the public site (used only for the /properties/{slug} 404 gate —
     * unrelated to the dashboard's "any authenticated" view() above).
     */
    public function viewPublic(User $user, Property $property): bool
    {
        return $user->isAdmin() || $property->agent_id === $user->id;
    }

    /**
     * Determine whether the user can create properties.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the property.
     */
    public function update(User $user, Property $property): bool
    {
        return $user->isAdmin() || $property->agent_id === $user->id;
    }

    /**
     * Determine whether the user can delete the property.
     */
    public function delete(User $user, Property $property): bool
    {
        return $user->isAdmin() || $property->agent_id === $user->id;
    }

    /**
     * Determine whether the user can publish or unpublish the property.
     */
    public function publish(User $user, Property $property): bool
    {
        return $this->update($user, $property);
    }
}
