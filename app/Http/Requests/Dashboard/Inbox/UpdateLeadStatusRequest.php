<?php

namespace App\Http\Requests\Dashboard\Inbox;

use App\Enums\LeadStatus;
use App\Enums\OfferStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Offers have their own status enum (adds converted/rejected); the
     * other two lead types share LeadStatus. The route parameter name
     * (message/offer/propertyRequest) tells us which one applies.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $enumClass = $this->route('offer') !== null ? OfferStatus::class : LeadStatus::class;

        return [
            'status' => ['required', Rule::enum($enumClass)],
        ];
    }
}
