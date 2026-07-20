<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\LeadNote;
use App\Models\PropertyOffer;
use App\Models\PropertyRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class LeadInboxService
{
    /**
     * Admin sees every lead; an agent only those assigned to them. This is
     * the same rule DashboardService::scopedLeads() uses for the sidebar
     * badge counts — keep both in sync. Enforced here in the query AND by
     * LeadPolicy on every route (view/update/assign/notes) — direct-URL
     * access to another agent's lead still 403s even if a query changes.
     *
     * @template TModel of ContactMessage|PropertyOffer|PropertyRequest
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scope(Builder $query, User $user): Builder
    {
        return $query->when(
            $user->isAgent(),
            fn (Builder $scoped) => $scoped->where('assigned_to', $user->id)
        );
    }

    /**
     * @template TModel of ContactMessage|PropertyOffer|PropertyRequest
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @param  list<string>  $nameColumns
     * @return Builder<TModel>
     */
    public function filter(Builder $query, array $filters, array $nameColumns): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['assigned_to'] ?? null, fn (Builder $q, $agentId) => $q->where('assigned_to', $agentId))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when($filters['search'] ?? null, function (Builder $q, string $search) use ($nameColumns) {
                $q->where(function (Builder $q) use ($search, $nameColumns) {
                    $q->whereLike('phone', "%{$search}%");

                    foreach ($nameColumns as $column) {
                        $q->orWhereLike($column, "%{$search}%");
                    }
                });
            });
    }

    public function updateStatus(ContactMessage|PropertyOffer|PropertyRequest $lead, string $status): void
    {
        $lead->update(['status' => $status]);
    }

    public function assign(ContactMessage|PropertyOffer|PropertyRequest $lead, ?int $agentId): void
    {
        $lead->update(['assigned_to' => $agentId]);
    }

    public function addNote(ContactMessage|PropertyOffer|PropertyRequest $lead, User $author, string $body): LeadNote
    {
        return $lead->notes()->create([
            'user_id' => $author->id,
            'body' => $body,
        ]);
    }

    /**
     * Assignable agents for the admin-only filter and assign dropdown.
     *
     * @return list<array{id: int, name: string}>|null
     */
    public function agentOptions(User $user): ?array
    {
        if (! $user->isAdmin()) {
            return null;
        }

        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $agent): array => ['id' => $agent->id, 'name' => $agent->name])
            ->all();
    }
}
