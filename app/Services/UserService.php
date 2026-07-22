<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UserService
{
    private const AVATAR_DIRECTORY = 'agents';

    public function __construct(private readonly ImageService $imageService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $avatar): User
    {
        if ($avatar !== null) {
            $data['avatar_path'] = $this->imageService->storeSingle($avatar, self::AVATAR_DIRECTORY);
        }

        return User::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, ?UploadedFile $avatar): User
    {
        if ($avatar !== null) {
            $previousAvatar = $user->avatar_path;
            $data['avatar_path'] = $this->imageService->storeSingle($avatar, self::AVATAR_DIRECTORY);
            $this->imageService->deleteSingle($previousAvatar);
        }

        $user->update($data);

        return $user;
    }

    public function toggleActive(User $user): User
    {
        $user->update(['is_active' => ! $user->is_active]);

        return $user;
    }

    /**
     * Deleting an agent must never orphan their properties or leads: both are
     * reassigned to the admin performing the deletion, who becomes the
     * temporary owner and can hand them to another agent later from the
     * normal property/inbox pages — no null agent_id, no "unassigned
     * property" state for the rest of the app to guard against. Lead notes
     * are personal commentary tied to authorship, so they're removed with
     * the agent instead of being reassigned.
     */
    public function delete(User $user, User $actingAdmin): void
    {
        DB::transaction(function () use ($user, $actingAdmin): void {
            $user->properties()->update(['agent_id' => $actingAdmin->id]);

            $user->assignedContactMessages()->update(['assigned_to' => $actingAdmin->id]);
            $user->assignedPropertyOffers()->update(['assigned_to' => $actingAdmin->id]);
            $user->assignedPropertyRequests()->update(['assigned_to' => $actingAdmin->id]);

            $user->leadNotes()->delete();

            $this->imageService->deleteSingle($user->avatar_path);

            $user->delete();
        });
    }
}
