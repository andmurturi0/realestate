<?php

namespace App\Models;

use Database\Factories\TeamMemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class TeamMember extends Model
{
    /** @use HasFactory<TeamMemberFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    public array $translatable = ['position'];

    protected $fillable = [
        'name',
        'position',
        'photo_path',
        'sort_order',
        'is_active',
    ];

    protected $attributes = [
        'sort_order' => 0,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The only team members the public site may ever see.
     *
     * @param  Builder<TeamMember>  $query
     * @return Builder<TeamMember>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->photo_path === null) {
                return null;
            }

            return Str::startsWith($this->photo_path, ['http://', 'https://'])
                ? $this->photo_path
                : Storage::disk('supabase')->url($this->photo_path);
        });
    }
}
