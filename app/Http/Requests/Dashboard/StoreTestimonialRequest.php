<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Testimonial;

class StoreTestimonialRequest extends TestimonialRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Testimonial::class) ?? false;
    }
}
