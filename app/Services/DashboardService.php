<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Enums\OfferStatus;
use App\Enums\PropertyStatus;
use App\Models\ContactMessage;
use App\Models\Property;
use App\Models\PropertyOffer;
use App\Models\PropertyRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * New-lead counts for the sidebar badges — exactly one query per lead source.
     * Admin counts every new lead; an agent only those assigned to them.
     *
     * @return array{messages: int, offers: int, requests: int}
     */
    public function inboxBadgeCounts(User $user): array
    {
        return [
            'messages' => $this->scopedLeads(ContactMessage::query(), $user)
                ->where('status', LeadStatus::New)
                ->count(),
            'offers' => $this->scopedLeads(PropertyOffer::query(), $user)
                ->where('status', OfferStatus::New)
                ->count(),
            'requests' => $this->scopedLeads(PropertyRequest::query(), $user)
                ->where('status', LeadStatus::New)
                ->count(),
        ];
    }

    /**
     * The four overview stat cards: one aggregate query for properties and one
     * per lead source (new + this-month counts combined), all role-scoped.
     *
     * @return array{properties: int, published: int, newLeads: int, monthLeads: int}
     */
    public function overviewStats(User $user): array
    {
        $properties = Property::query()
            ->when($user->isAgent(), fn (Builder $query) => $query->where('agent_id', $user->id))
            ->selectRaw(
                'count(*) as total, sum(case when status = ? then 1 else 0 end) as published',
                [PropertyStatus::Published->value]
            )
            ->first();

        $monthStart = now()->startOfMonth()->toDateTimeString();

        $leadTotals = collect([
            ContactMessage::query(),
            PropertyOffer::query(),
            PropertyRequest::query(),
        ])->map(
            fn (Builder $query) => $this->scopedLeads($query, $user)
                ->selectRaw(
                    'sum(case when status = ? then 1 else 0 end) as new_count, '
                    .'sum(case when created_at >= ? then 1 else 0 end) as month_count',
                    ['new', $monthStart]
                )
                ->first()
        );

        return [
            'properties' => (int) ($properties->total ?? 0),
            'published' => (int) ($properties->published ?? 0),
            'newLeads' => (int) $leadTotals->sum('new_count'),
            'monthLeads' => (int) $leadTotals->sum('month_count'),
        ];
    }

    /**
     * The most recent leads across all three sources, shaped for the overview
     * list. Three queries (one per source), merged and trimmed in memory.
     *
     * @return Collection<int, array{type: string, id: int, name: string, phone: string, status: string, created_at: string|null}>
     */
    public function recentLeads(User $user, int $limit = 5): Collection
    {
        $messages = $this->scopedLeads(ContactMessage::query(), $user)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (ContactMessage $lead): array => $this->presentLead(
                'message', $lead->id, $lead->full_name, $lead->phone, $lead->status->value, $lead->created_at?->toIso8601String()
            ));

        $offers = $this->scopedLeads(PropertyOffer::query(), $user)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (PropertyOffer $lead): array => $this->presentLead(
                'offer', $lead->id, $lead->first_name.' '.$lead->last_name, $lead->phone, $lead->status->value, $lead->created_at?->toIso8601String()
            ));

        $requests = $this->scopedLeads(PropertyRequest::query(), $user)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (PropertyRequest $lead): array => $this->presentLead(
                'request', $lead->id, $lead->first_name.' '.$lead->last_name, $lead->phone, $lead->status->value, $lead->created_at?->toIso8601String()
            ));

        return $messages->concat($offers)
            ->concat($requests)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values();
    }

    /**
     * @return array{type: string, id: int, name: string, phone: string, status: string, created_at: string|null}
     */
    private function presentLead(string $type, int $id, string $name, string $phone, string $status, ?string $createdAt): array
    {
        return [
            'type' => $type,
            'id' => $id,
            'name' => $name,
            'phone' => $phone,
            'status' => $status,
            'created_at' => $createdAt,
        ];
    }

    /**
     * Admin sees everything; an agent only leads assigned to them.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function scopedLeads(Builder $query, User $user): Builder
    {
        return $query->when(
            $user->isAgent(),
            fn (Builder $scoped) => $scoped->where('assigned_to', $user->id)
        );
    }
}
