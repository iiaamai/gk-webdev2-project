<?php

namespace Database\Factories;

use App\Models\DriverAttendanceLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverAttendanceLog>
 */
class DriverAttendanceLogFactory extends Factory
{
    protected $model = DriverAttendanceLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'driver_id' => User::factory()->driver(),
            'timed_in_at' => now('Asia/Manila'),
            'timed_in_by_id' => User::factory()->staff(),
            'timed_out_at' => null,
            'timed_out_by_id' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'timed_out_at' => now('Asia/Manila'),
            'timed_out_by_id' => $attributes['timed_in_by_id'] ?? User::factory()->staff(),
        ]);
    }
}
