<?php

namespace App\Services;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TestimonialService
{
    private const PHOTO_DIRECTORY = 'testimonials';

    public function __construct(private readonly ImageService $imageService) {}

    /**
     * @return Collection<int, Testimonial>
     */
    public function listForDashboard(): Collection
    {
        return Testimonial::query()->orderBy('sort_order')->orderBy('id')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $photo): Testimonial
    {
        if ($photo !== null) {
            $data['photo_path'] = $this->imageService->storeSingle($photo, self::PHOTO_DIRECTORY);
        }

        if (! array_key_exists('sort_order', $data) || $data['sort_order'] === null) {
            $data['sort_order'] = ((int) Testimonial::max('sort_order')) + 1;
        }

        return Testimonial::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Testimonial $testimonial, array $data, ?UploadedFile $photo): Testimonial
    {
        if ($photo !== null) {
            $previousPhoto = $testimonial->photo_path;
            $data['photo_path'] = $this->imageService->storeSingle($photo, self::PHOTO_DIRECTORY);
            $this->imageService->deleteSingle($previousPhoto);
        }

        $testimonial->update($data);

        return $testimonial;
    }

    public function delete(Testimonial $testimonial): void
    {
        $this->imageService->deleteSingle($testimonial->photo_path);
        $testimonial->delete();
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $index => $id) {
                Testimonial::whereKey($id)->update(['sort_order' => $index]);
            }
        });
    }
}
