<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\Property;

class ContactMessageService
{
    /**
     * @param  array<string, mixed>  $data
     *
     * Honeypot filled → a bot. Pretend success, write nothing (mirrors the
     * "refuzohet në heshtje" convention set for the other public lead forms).
     */
    public function submit(Property $property, array $data, string $ip): ?ContactMessage
    {
        if (filled($data['website'] ?? null)) {
            return null;
        }

        return ContactMessage::create([
            'property_id' => $property->id,
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'message' => $data['message'],
            'ip_address' => $ip,
        ]);
    }
}
