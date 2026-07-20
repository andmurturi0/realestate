<?php

namespace App\Models;

use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    public array $translatable = ['quote'];

    protected $fillable = [
        'author_name',
        'company',
        'quote',
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
     * The only testimonials the public site may ever see.
     *
     * @param  Builder<Testimonial>  $query
     * @return Builder<Testimonial>
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
