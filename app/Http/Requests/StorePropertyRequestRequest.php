<?php

namespace App\Http\Requests;

use App\Enums\PropertyCategory;
use App\Enums\RequestType;
use App\Enums\SurfaceUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequestRequest extends FormRequest
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
            'request_type' => ['required', Rule::enum(RequestType::class)],
            'category' => ['required', Rule::enum(PropertyCategory::class)],
            'location_id' => ['required', Rule::exists('locations', 'id')],
            'budget' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999'],
            'surface_unit' => ['required', Rule::enum(SurfaceUnit::class)],
            'surface_min' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'surface_max' => ['nullable', 'numeric', 'min:0', 'max:99999999', 'gte:surface_min'],
            'details' => ['nullable', 'string', 'max:2000'],
            // Honeypot: real visitors never fill this (hidden off-screen).
            // Never "required" — a bot must be free to fill it and pass validation.
            'website' => ['nullable', 'string'],
            // Minimum time-on-form check: epoch ms captured when the form mounted.
            // Never "required" either — its absence is just another bot signal.
            'form_rendered_at' => ['nullable', 'integer'],
        ];
    }

    /**
     * The validated attributes ready for persistence: the euro budget is
     * converted to integer cents, and min/max surface are normalized to m²
     * from whichever unit the visitor picked (surface_unit itself is kept
     * as-is, for display only — surfaces are always stored in m²).
     * Honeypot/timing fields are excluded — bot detection is a separate
     * concern, see isBotSubmission().
     *
     * @return array<string, mixed>
     */
    public function requestData(): array
    {
        $data = $this->safe()->except(['website', 'form_rendered_at']);

        $unit = SurfaceUnit::from($data['surface_unit']);

        $data['budget_max'] = (int) round((float) $data['budget'] * 100);
        unset($data['budget']);

        $data['surface_min_m2'] = $data['surface_min'] !== null ? $unit->toSquareMetres((float) $data['surface_min']) : null;
        $data['surface_max_m2'] = $data['surface_max'] !== null ? $unit->toSquareMetres((float) $data['surface_max']) : null;
        unset($data['surface_min'], $data['surface_max']);

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
