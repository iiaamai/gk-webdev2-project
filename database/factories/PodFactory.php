<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Pod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pod>
 */
class PodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'photo_paths' => [
                'bookings/demo/pod-1.jpg',
                'bookings/demo/pod-2.jpg',
            ],
            'captured_at' => now(),
        ];
    }
}
