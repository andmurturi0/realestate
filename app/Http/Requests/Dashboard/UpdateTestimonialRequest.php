<?php

namespace App\Http\Requests\Dashboard;

class UpdateTestimonialRequest extends TestimonialRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('testimonial')) ?? false;
    }
}
