<?php

namespace App\Http\Controllers\Dashboard\Inbox;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Inbox\AssignLeadRequest;
use App\Http\Requests\Dashboard\Inbox\StoreLeadNoteRequest;
use App\Http\Requests\Dashboard\Inbox\UpdateLeadStatusRequest;
use App\Http\Resources\PropertyCardResource;
use App\Models\PropertyRequest;
use App\Services\LeadInboxService;
use App\Services\PropertyRequestMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RequestController extends Controller
{
    public function __construct(
        private readonly LeadInboxService $inboxService,
        private readonly PropertyRequestMatcher $matcher,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', PropertyRequest::class);

        $user = $request->user();
        $filters = $request->only(['status', 'assigned_to', 'date_from', 'date_to', 'search']);

        $query = $this->inboxService->filter(
            $this->inboxService->scope(PropertyRequest::query(), $user),
            $filters,
            ['first_name', 'last_name']
        );

        $requests = $query->with(['assignedTo:id,name', 'location:id,parent_id,name'])
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (PropertyRequest $propertyRequest): array => [
                'id' => $propertyRequest->id,
                'name' => "{$propertyRequest->first_name} {$propertyRequest->last_name}",
                'phone' => $propertyRequest->phone,
                'category' => $propertyRequest->category->value,
                'request_type' => $propertyRequest->request_type->value,
                'budget_max' => $propertyRequest->budget_max,
                'status' => $propertyRequest->status->value,
                'assigned_agent' => $propertyRequest->assignedTo?->name,
                'created_at' => $propertyRequest->created_at?->toIso8601String(),
            ]);

        return Inertia::render('dashboard/inbox/Requests', [
            'requests' => $requests,
            'filters' => $filters,
            'agents' => $this->inboxService->agentOptions($user),
            'selected' => fn () => $this->presentSelected($request),
        ]);
    }

    public function updateStatus(UpdateLeadStatusRequest $request, PropertyRequest $propertyRequest): RedirectResponse
    {
        Gate::authorize('update', $propertyRequest);

        $this->inboxService->updateStatus($propertyRequest, $request->validated('status'));

        return back();
    }

    public function assign(AssignLeadRequest $request, PropertyRequest $propertyRequest): RedirectResponse
    {
        Gate::authorize('assign', $propertyRequest);

        $this->inboxService->assign($propertyRequest, $request->validated('assigned_to'));

        return back();
    }

    public function storeNote(StoreLeadNoteRequest $request, PropertyRequest $propertyRequest): RedirectResponse
    {
        Gate::authorize('update', $propertyRequest);

        $this->inboxService->addNote($propertyRequest, $request->user(), $request->validated('body'));

        return back();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function presentSelected(Request $request): ?array
    {
        $id = $request->query('selected');

        if (! $id) {
            return null;
        }

        $propertyRequest = PropertyRequest::query()
            ->with([
                'assignedTo:id,name',
                'location:id,parent_id,name',
                'notes' => fn ($q) => $q->with('user:id,name'),
            ])
            ->find($id);

        if ($propertyRequest === null) {
            return null;
        }

        Gate::authorize('view', $propertyRequest);

        $matches = $this->matcher->matches($propertyRequest)
            ->map(fn ($property): array => (new PropertyCardResource($property))->resolve())
            ->values();

        return [
            'id' => $propertyRequest->id,
            'first_name' => $propertyRequest->first_name,
            'last_name' => $propertyRequest->last_name,
            'phone' => $propertyRequest->phone,
            'request_type' => $propertyRequest->request_type->value,
            'category' => $propertyRequest->category->value,
            'location' => $propertyRequest->location->getTranslation('name', 'sq'),
            'budget_max' => $propertyRequest->budget_max,
            'surface_min_m2' => $propertyRequest->surface_min_m2,
            'surface_max_m2' => $propertyRequest->surface_max_m2,
            'surface_unit' => $propertyRequest->surface_unit->value,
            'details' => $propertyRequest->details,
            'status' => $propertyRequest->status->value,
            'assigned_to' => $propertyRequest->assigned_to,
            'created_at' => $propertyRequest->created_at?->toIso8601String(),
            'can_assign' => Gate::allows('assign', $propertyRequest),
            'matches' => $matches,
            'notes' => $propertyRequest->notes->map(fn ($note): array => [
                'id' => $note->id,
                'body' => $note->body,
                'author' => $note->user->name,
                'created_at' => $note->created_at?->toIso8601String(),
            ]),
        ];
    }
}
