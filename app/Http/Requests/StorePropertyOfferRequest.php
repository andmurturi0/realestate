<?php

namespace App\Http\Requests;

use App\Enums\ListingType;
use App\Enums\PropertyCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^(\+383|0)4[3-9][0-9]{6}$/'],
            'listing_type' => ['required', Rule::enum(ListingType::class)],
            'category' => ['required', Rule::enum(PropertyCategory::class)],
            'location_id' => ['required', Rule::exists('locations', 'id')],
            'surface_m2' => ['required', 'numeric', 'min:1', 'max:99999999'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999'],
            // Honeypot: real visitors never fill this (hidden off-screen).
            // Never "required" — a bot must be free to fill it and pass validation.
            'website' => ['nullable', 'string'],
            // Minimum time-on-form check: epoch ms captured when the form mounted.
            // Never "required" either — its absence is just another bot signal.
            'form_rendered_at' => ['nullable', 'integer'],
        ];
    }

    /**
     * The validated attributes ready for persistence: the euro price is
     * converted to integer cents. Honeypot/timing fields are excluded —
     * bot detection is a separate concern, see isBotSubmission().
     *
     * @return array<string, mixed>
     */
    public function offerData(): array
    {
        $data = $this->safe()->except(['website', 'form_rendered_at']);

        $data['asking_price'] = (int) round((float) $data['price'] * 100);
        unset($data['price']);

        return $data;
    }

    /**
     * Honeypot filled, or submitted implausibly fast, means a bot.
     * A missing timestamp (JS disabled or a bot that skips the field)
     * counts against the visitor the same way a too-fast submit does.
     */
    public function isBotSubmission(int $minSeconds = 3): bool
    {
        if (filled($this->input('website'))) {
            return true;
        }

        $renderedAt = $this->input('form_rendered_at');

        return $renderedAt === null || (now()->valueOf() - (int) $renderedAt) < $minSeconds * 1000;
    }
}
