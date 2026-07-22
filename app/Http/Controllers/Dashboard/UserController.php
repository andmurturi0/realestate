<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreUserRequest;
use App\Http\Requests\Dashboard\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', User::class);

        $users = User::query()
            ->withCount(['properties', 'assignedContactMessages', 'assignedPropertyOffers', 'assignedPropertyRequests'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => $this->present($user))
            ->values();

        return Inertia::render('dashboard/agents/Index', [
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('dashboard/agents/Create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userService->create($request->userData(), $request->file('avatar'));

        return to_route('dashboard.agents.index')->with('success', 'Agjenti u shtua.');
    }

    public function edit(User $user): Response
    {
        Gate::authorize('update', $user);

        return Inertia::render('dashboard/agents/Edit', [
            'agent' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'whatsapp' => $user->whatsapp,
                'role' => $user->role->value,
                'bio' => $user->getTranslations('bio'),
                'avatar_url' => $user->avatar_url,
                'is_active' => $user->is_active,
            ],
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->update($user, $request->userData(), $request->file('avatar'));

        return to_route('dashboard.agents.index')->with('success', 'Agjenti u përditësua.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $this->userService->delete($user, $request->user());

        return to_route('dashboard.agents.index')->with('success', 'Agjenti u fshi.');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        abort_if($user->id === $request->user()->id, 403);

        $this->userService->toggleActive($user);

        return back()->with('success', $user->is_active ? 'Agjenti u aktivizua.' : 'Agjenti u çaktivizua.');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role->value,
            'avatar_url' => $user->avatar_url,
            'is_active' => $user->is_active,
            'properties_count' => $user->properties_count,
            'leads_count' => $user->assigned_contact_messages_count
                + $user->assigned_property_offers_count
                + $user->assigned_property_requests_count,
        ];
    }
}
