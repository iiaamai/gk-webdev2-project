<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use App\Models\Concerns\Archivable;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'plate_number',
    'brand',
    'color',
    'pricing_id',
    'status',
    'driver_id',
])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use Archivable, HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'available',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => VehicleStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    /**
     * Display type from Pricing.
     *
     * @return Attribute<string, never>
     */
    protected function type(): Attribute
    {
        return Attribute::get(function (): string {
            $this->loadMissing('pricing');

            return (string) ($this->pricing?->vehicle_type ?? '—');
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * @return BelongsTo<Pricing, $this>
     */
    public function pricing(): BelongsTo
    {
        return $this->belongsTo(Pricing::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
