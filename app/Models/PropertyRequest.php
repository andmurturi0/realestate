<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\LeadStatus;
use App\Enums\PropertyCategory;
use App\Enums\RequestType;
use App\Enums\SurfaceUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PropertyRequest extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'request_type',
        'category',
        'location_id',
        'budget_max',
        'surface_min_m2',
        'surface_max_m2',
        'surface_unit',
        'details',
        'status',
        'assigned_to',
        'ip_address',
    ];

    protected $attributes = [
        'status' => 'new',
        'surface_unit' => 'm2',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_type' => RequestType::class,
            'category' => PropertyCategory::class,
            'budget_max' => MoneyCast::class,
            'surface_min_m2' => 'float',
            'surface_max_m2' => 'float',
            'surface_unit' => SurfaceUnit::class,
            'status' => LeadStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
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
