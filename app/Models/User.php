<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasTranslations, Notifiable;

    /** @var list<string> */
    public array $translatable = ['bio'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'whatsapp',
        'avatar_path',
        'bio',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isAgent(): bool
    {
        return $this->role === UserRole::Agent;
    }

    /**
     * @return HasMany<Property, $this>
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'agent_id');
    }

    /**
     * @return HasMany<LeadNote, $this>
     */
    public function leadNotes(): HasMany
    {
        return $this->hasMany(LeadNote::class);
    }

    /**
     * @return HasMany<ContactMessage, $this>
     */
    public function assignedContactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class, 'assigned_to');
    }

    /**
     * @return HasMany<PropertyOffer, $this>
     */
    public function assignedPropertyOffers(): HasMany
    {
        return $this->hasMany(PropertyOffer::class, 'assigned_to');
    }

    /**
     * @return HasMany<PropertyRequest, $this>
     */
    public function assignedPropertyRequests(): HasMany
    {
        return $this->hasMany(PropertyRequest::class, 'assigned_to');
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->avatar_path === null) {
                return null;
            }

            return Str::startsWith($this->avatar_path, ['http://', 'https://'])
                ? $this->avatar_path
                : Storage::disk('supabase')->url($this->avatar_path);
        });
    }
}
