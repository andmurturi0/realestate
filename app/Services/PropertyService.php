<?php

namespace App\Services;

use App\Enums\PropertyStatus;
use App\Models\Property;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PropertyService
{
    /** @var list<string> */
    private const SORTABLE_COLUMNS = ['reference_code', 'price', 'views_count', 'status', 'created_at'];

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $featureIds
     */
    public function create(array $data, array $featureIds, User $user): Property
    {
        // Ownership is never taken from the payload: the creator owns the
        // property unless an admin explicitly assigns another agent.
        $data['agent_id'] = $user->isAdmin() && ! empty($data['agent_id'])
            ? (int) $data['agent_id']
            : $user->id;

        $data = $this->applyPublishing($data);

        return DB::transaction(function () use ($data, $featureIds): Property {
            $property = Property::create($data);
            $property->features()->sync($featureIds);

            return $property;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $featureIds
     */
    public function update(Property $property, array $data, array $featureIds, User $user): Property
    {
        if (! $user->isAdmin() || empty($data['agent_id'])) {
            unset($data['agent_id']);
        }

        $data = $this->applyPublishing($data, $property);

        return DB::transaction(function () use ($property, $data, $featureIds): Property {
            $property->update($data);
            $property->features()->sync($featureIds);

            return $property;
        });
    }

    public function delete(Property $property): void
    {
        $property->delete();
    }

    /**
     * Dashboard listing: every agent sees every property (edit rights are a
     * policy concern), filtered and sorted from whitelisted query params.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Property>
     */
    public function paginateForDashboard(User $user, array $filters): LengthAwarePaginator
    {
        $query = Property::query()
            ->with(['agent:id,name', 'location:id,name', 'primaryImage'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when($filters['listing_type'] ?? null, fn ($q, $type) => $q->where('listing_type', $type))
            ->when(
                $user->isAdmin() ? ($filters['agent'] ?? null) : null,
                fn ($q, $agentId) => $q->where('agent_id', $agentId)
            )
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->whereLike('reference_code', "%{$search}%")
                        ->orWhereLike('title->sq', "%{$search}%")
                        ->orWhereLike('title->en', "%{$search}%")
                        ->orWhereLike('title->de', "%{$search}%");
                });
            });

        $sort = in_array($filters['sort'] ?? null, self::SORTABLE_COLUMNS, true)
            ? $filters['sort']
            : 'created_at';
        $direction = ($filters['direction'] ?? null) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * Publishing stamps published_at the first time a property goes
     * published; the timestamp is kept on later status changes.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyPublishing(array $data, ?Property $property = null): array
    {
        if (($data['status'] ?? null) === PropertyStatus::Published->value && $property?->published_at === null) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
