<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ReorderTestimonialsRequest;
use App\Http\Requests\Dashboard\StoreTestimonialRequest;
use App\Http\Requests\Dashboard\UpdateTestimonialRequest;
use App\Models\Testimonial;
use App\Services\TestimonialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TestimonialController extends Controller
{
    public function __construct(private readonly TestimonialService $testimonialService) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', Testimonial::class);

        return Inertia::render('dashboard/testimonials/Index', [
            'testimonials' => $this->testimonialService->listForDashboard()
                ->map(fn (Testimonial $testimonial): array => $this->present($testimonial))
                ->values(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Testimonial::class);

        return Inertia::render('dashboard/testimonials/Create');
    }

    public function store(StoreTestimonialRequest $request): RedirectResponse
    {
        $this->testimonialService->create($request->testimonialData(), $request->file('photo'));

        return to_route('dashboard.testimonials.index')->with('success', 'Dëshmia u shtua.');
    }

    public function edit(Testimonial $testimonial): Response
    {
        Gate::authorize('update', $testimonial);

        return Inertia::render('dashboard/testimonials/Edit', [
            'testimonial' => [
                'id' => $testimonial->id,
                'author_name' => $testimonial->author_name,
                'company' => $testimonial->company,
                'quote' => $testimonial->getTranslations('quote'),
                'photo_url' => $testimonial->photo_url,
                'sort_order' => $testimonial->sort_order,
                'is_active' => $testimonial->is_active,
            ],
        ]);
    }

    public function update(UpdateTestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $this->testimonialService->update($testimonial, $request->testimonialData(), $request->file('photo'));

        return to_route('dashboard.testimonials.index')->with('success', 'Dëshmia u përditësua.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        Gate::authorize('delete', $testimonial);

        $this->testimonialService->delete($testimonial);

        return to_route('dashboard.testimonials.index')->with('success', 'Dëshmia u fshi.');
    }

    public function reorder(ReorderTestimonialsRequest $request): RedirectResponse
    {
        $this->testimonialService->reorder($request->orderedIds());

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Testimonial $testimonial): array
    {
        return [
            'id' => $testimonial->id,
            'author_name' => $testimonial->author_name,
            'company' => $testimonial->company,
            'quote' => $testimonial->getTranslation('quote', 'sq'),
            'photo_url' => $testimonial->photo_url,
            'sort_order' => $testimonial->sort_order,
            'is_active' => $testimonial->is_active,
        ];
    }
}
