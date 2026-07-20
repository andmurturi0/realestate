<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\PropertyRequest;
use App\Models\User;
use App\Notifications\NewLeadReceived;
use Illuminate\Support\Facades\Notification;

class PropertyRequestService
{
    /**
     * @param  array<string, mixed>  $data
     *
     * $isBot (honeypot filled or submitted implausibly fast) → pretend
     * success, write nothing (mirrors ContactMessageService::submit()).
     */
    public function submit(array $data, bool $isBot, string $ip): ?PropertyRequest
    {
        if ($isBot) {
            return null;
        }

        $request = PropertyRequest::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'request_type' => $data['request_type'],
            'category' => $data['category'],
            'location_id' => $data['location_id'],
            'budget_max' => $data['budget_max'],
            'surface_min_m2' => $data['surface_min_m2'],
            'surface_max_m2' => $data['surface_max_m2'],
            'surface_unit' => $data['surface_unit'],
            'details' => $data['details'],
            'ip_address' => $ip,
        ]);

        Notification::send(
            User::query()->where('role', UserRole::Admin)->get(),
            new NewLeadReceived($request)
        );

        return $request;
    }
}
