<?php

namespace App\Models;

use App\Enums\FeatureGroup;
use App\Services\FacetOptionsService;
use Database\Factories\FeatureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class Feature extends Model
{
    /** @use HasFactory<FeatureFactory> */
    use HasFactory, HasTranslations;

    public $timestamps = false;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = [
        'key',
        'name',
        'icon',
        'group',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => FacetOptionsService::flush());
        static::deleted(fn () => FacetOptionsService::flush());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'group' => FeatureGroup::class,
        ];
    }

    /**
     * @return BelongsToMany<Property, $this>
     */
    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class);
    }
}
