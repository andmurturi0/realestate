<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ReorderTeamMembersRequest;
use App\Http\Requests\Dashboard\StoreTeamMemberRequest;
use App\Http\Requests\Dashboard\UpdateTeamMemberRequest;
use App\Models\TeamMember;
use App\Services\TeamMemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeamMemberController extends Controller
{
    public function __construct(private readonly TeamMemberService $teamMemberService) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', TeamMember::class);

        return Inertia::render('dashboard/team-members/Index', [
            'teamMembers' => $this->teamMemberService->listForDashboard()
                ->map(fn (TeamMember $teamMember): array => $this->present($teamMember))
                ->values(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', TeamMember::class);

        return Inertia::render('dashboard/team-members/Create');
    }

    public function store(StoreTeamMemberRequest $request): RedirectResponse
    {
        $this->teamMemberService->create($request->teamMemberData(), $request->file('photo'));

        return to_route('dashboard.team-members.index')->with('success', 'Anëtari u shtua.');
    }

    public function edit(TeamMember $teamMember): Response
    {
        Gate::authorize('update', $teamMember);

        return Inertia::render('dashboard/team-members/Edit', [
            'teamMember' => [
                'id' => $teamMember->id,
                'name' => $teamMember->name,
                'position' => $teamMember->getTranslations('position'),
                'photo_url' => $teamMember->photo_url,
                'sort_order' => $teamMember->sort_order,
                'is_active' => $teamMember->is_active,
            ],
        ]);
    }

    public function update(UpdateTeamMemberRequest $request, TeamMember $teamMember): RedirectResponse
    {
        $this->teamMemberService->update($teamMember, $request->teamMemberData(), $request->file('photo'));

        return to_route('dashboard.team-members.index')->with('success', 'Anëtari u përditësua.');
    }

    public function destroy(TeamMember $teamMember): RedirectResponse
    {
        Gate::authorize('delete', $teamMember);

        $this->teamMemberService->delete($teamMember);

        return to_route('dashboard.team-members.index')->with('success', 'Anëtari u fshi.');
    }

    public function reorder(ReorderTeamMembersRequest $request): RedirectResponse
    {
        $this->teamMemberService->reorder($request->orderedIds());

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(TeamMember $teamMember): array
    {
        return [
            'id' => $teamMember->id,
            'name' => $teamMember->name,
            'position' => $teamMember->getTranslation('position', 'sq'),
            'photo_url' => $teamMember->photo_url,
            'sort_order' => $teamMember->sort_order,
            'is_active' => $teamMember->is_active,
        ];
    }
}
