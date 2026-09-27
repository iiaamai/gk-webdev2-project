<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Pricing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pricing = Pricing::query()->where('vehicle_type', '4-wheeler truck')->first()
            ?? Pricing::factory()->create([
                'vehicle_type' => '4-wheeler truck',
                'amount' => 9200.00,
            ]);

        return [
            'booking_number' => sprintf('GK-%s-%04d', now('Asia/Manila')->year, fake()->unique()->numberBetween(1, 9999)),
            'customer_id' => User::factory()->customer(),
            'driver_id' => null,
            'vehicle_id' => null,
            'pricing_id' => $pricing->id,
            'booking_datetime' => now('Asia/Manila')->addDay(),
            'posting_date' => now('Asia/Manila')->toDateString(),
            'pickup_address' => 'Makati City, Metro Manila',
            'pickup_lat' => 14.5547,
            'pickup_lng' => 121.0244,
            'dropoff_address' => 'Quezon City, Metro Manila',
            'dropoff_lat' => 14.6760,
            'dropoff_lng' => 121.0437,
            'cargo_desc' => fake()->sentence(),
            'additional_requirements' => null,
            'status' => BookingStatus::Pending,
            'is_locked' => false,
            'accepted_at' => null,
            'gatepass_path' => null,
        ];
    }

    public function withGatepass(): static
    {
        return $this->state(fn (array $attributes) => [
            'gatepass_path' => 'bookings/demo/gatepass.jpg',
        ]);
    }
}
