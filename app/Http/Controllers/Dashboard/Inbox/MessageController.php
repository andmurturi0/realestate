<?php

namespace App\Http\Controllers\Dashboard\Inbox;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Inbox\AssignLeadRequest;
use App\Http\Requests\Dashboard\Inbox\StoreLeadNoteRequest;
use App\Http\Requests\Dashboard\Inbox\UpdateLeadStatusRequest;
use App\Models\ContactMessage;
use App\Services\LeadInboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function __construct(private readonly LeadInboxService $inboxService) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ContactMessage::class);

        $user = $request->user();
        $filters = $request->only(['status', 'assigned_to', 'date_from', 'date_to', 'search']);

        $query = $this->inboxService->filter(
            $this->inboxService->scope(ContactMessage::query(), $user),
            $filters,
            ['full_name']
        );

        $messages = $query->with(['assignedTo:id,name', 'property:id,slug,reference_code,price,primaryImage'])
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ContactMessage $message): array => [
                'id' => $message->id,
                'name' => $message->full_name,
                'phone' => $message->phone,
                'snippet' => Str::limit($message->message, 80),
                'status' => $message->status->value,
                'assigned_agent' => $message->assignedTo?->name,
                'has_property' => $message->property_id !== null,
                'created_at' => $message->created_at?->toIso8601String(),
            ]);

        return Inertia::render('dashboard/inbox/Messages', [
            'messages' => $messages,
            'filters' => $filters,
            'agents' => $this->inboxService->agentOptions($user),
            'selected' => fn () => $this->presentSelected($request),
        ]);
    }

    public function updateStatus(UpdateLeadStatusRequest $request, ContactMessage $message): RedirectResponse
    {
        Gate::authorize('update', $message);

        $this->inboxService->updateStatus($message, $request->validated('status'));

        return back();
    }

    public function assign(AssignLeadRequest $request, ContactMessage $message): RedirectResponse
    {
        Gate::authorize('assign', $message);

        $this->inboxService->assign($message, $request->validated('assigned_to'));

        return back();
    }

    public function storeNote(StoreLeadNoteRequest $request, ContactMessage $message): RedirectResponse
    {
        Gate::authorize('update', $message);

        $this->inboxService->addNote($message, $request->user(), $request->validated('body'));

        return back();
    }

    /**
     * The detail panel's data — a lazy prop so partial reloads (selecting a
     * row) skip re-sending the whole list. Looked up WITHOUT the inbox
     * scope on purpose: an agent typing another agent's id into `?selected=`
     * must still hit LeadPolicy and get a hard 403, not a silently empty
     * panel (FAZAT.md 7B done-criteria).
     *
     * @return array<string, mixed>|null
     */
    private function presentSelected(Request $request): ?array
    {
        $id = $request->query('selected');

        if (! $id) {
            return null;
        }

        $message = ContactMessage::query()
            ->with(['assignedTo:id,name', 'property.primaryImage', 'notes' => fn ($q) => $q->with('user:id,name')])
            ->find($id);

        if ($message === null) {
            return null;
        }

        Gate::authorize('view', $message);

        return [
            'id' => $message->id,
            'full_name' => $message->full_name,
            'phone' => $message->phone,
            'email' => $message->email,
            'message' => $message->message,
            'status' => $message->status->value,
            'assigned_to' => $message->assigned_to,
            'created_at' => $message->created_at?->toIso8601String(),
            'can_assign' => Gate::allows('assign', $message),
            'property' => $message->property ? [
                'id' => $message->property->id,
                'slug' => $message->property->slug,
                'reference_code' => $message->property->reference_code,
                'title' => $message->property->getTranslation('title', 'sq'),
                'price' => $message->property->price,
                'thumbnail_url' => $message->property->primaryImage?->thumbnail_url,
            ] : null,
            'notes' => $message->notes->map(fn ($note): array => [
                'id' => $note->id,
                'body' => $note->body,
                'author' => $note->user->name,
                'created_at' => $note->created_at?->toIso8601String(),
            ]),
        ];
    }
}
