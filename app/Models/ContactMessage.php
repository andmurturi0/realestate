<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ContactMessage extends Model
{
    /** @use HasFactory<\Database\Factories\ContactMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'property_id',
        'full_name',
        'phone',
        'email',
        'message',
        'status',
        'assigned_to',
        'ip_address',
    ];

    protected $attributes = [
        'status' => 'new',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ContactMessage $message): void {
            if ($message->property_id !== null && $message->assigned_to === null) {
                $message->assigned_to = Property::withTrashed()
                    ->whereKey($message->property_id)
                    ->value('agent_id');
            }
        });
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return MorphMany<LeadNote, $this>
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(LeadNote::class, 'notable')->latest('created_at');
    }
}
