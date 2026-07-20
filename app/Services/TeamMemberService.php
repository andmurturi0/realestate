<?php

namespace App\Services;

use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TeamMemberService
{
    private const PHOTO_DIRECTORY = 'team';

    public function __construct(private readonly ImageService $imageService) {}

    /**
     * @return Collection<int, TeamMember>
     */
    public function listForDashboard(): Collection
    {
        return TeamMember::query()->orderBy('sort_order')->orderBy('id')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $photo): TeamMember
    {
        if ($photo !== null) {
            $data['photo_path'] = $this->imageService->storeSingle($photo, self::PHOTO_DIRECTORY);
        }

        if (! array_key_exists('sort_order', $data) || $data['sort_order'] === null) {
            $data['sort_order'] = ((int) TeamMember::max('sort_order')) + 1;
        }

        return TeamMember::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TeamMember $teamMember, array $data, ?UploadedFile $photo): TeamMember
    {
        if ($photo !== null) {
            $previousPhoto = $teamMember->photo_path;
            $data['photo_path'] = $this->imageService->storeSingle($photo, self::PHOTO_DIRECTORY);
            $this->imageService->deleteSingle($previousPhoto);
        }

        $teamMember->update($data);

        return $teamMember;
    }

    public function delete(TeamMember $teamMember): void
    {
        $this->imageService->deleteSingle($teamMember->photo_path);
        $teamMember->delete();
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $index => $id) {
                TeamMember::whereKey($id)->update(['sort_order' => $index]);
            }
        });
    }
}
