<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\PropertyOffer;
use App\Models\User;
use App\Notifications\NewLeadReceived;
use Illuminate\Support\Facades\Notification;

class PropertyOfferService
{
    /**
     * @param  array<string, mixed>  $data
     *
     * $isBot (honeypot filled or submitted implausibly fast) → pretend
     * success, write nothing (mirrors ContactMessageService::submit()).
     */
    public function submit(array $data, bool $isBot, string $ip): ?PropertyOffer
    {
        if ($isBot) {
            return null;
        }

        $offer = PropertyOffer::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'listing_type' => $data['listing_type'],
            'category' => $data['category'],
            'location_id' => $data['location_id'],
            'surface_m2' => $data['surface_m2'],
            'asking_price' => $data['asking_price'],
            'ip_address' => $ip,
        ]);

        Notification::send(
            User::query()->where('role', UserRole::Admin)->get(),
            new NewLeadReceived($offer)
        );

        return $offer;
    }
}
