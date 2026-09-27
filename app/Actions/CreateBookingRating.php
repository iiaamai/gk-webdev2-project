<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Rating;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateBookingRating
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array{score: int, comment?: string|null}  $data
     */
    public function execute(User $customer, Booking $booking, array $data): Rating
    {
        if (! $customer->isCustomer() || $booking->customer_id !== $customer->id) {
            throw ValidationException::withMessages([
                'booking' => 'You can only rate your own bookings.',
            ]);
        }

        if ($booking->status !== BookingStatus::Completed) {
            throw ValidationException::withMessages([
                'booking' => 'You can only rate a completed booking.',
            ]);
        }

        if ($booking->rating()->exists()) {
            throw ValidationException::withMessages([
                'booking' => 'This booking has already been rated.',
            ]);
        }

        $rating = DB::transaction(function () use ($customer, $booking, $data): Rating {
            return Rating::query()->create([
                'booking_id' => $booking->id,
                'customer_id' => $customer->id,
                'score' => $data['score'],
                'comment' => $data['comment'] ?? null,
            ]);
        });

        $this->activityLogger->log(
            action: 'rating.created',
            subject: $booking,
            description: "Customer rated booking {$booking->booking_number} ({$rating->score}/5).",
            properties: [
                'score' => $rating->score,
                'rating_id' => $rating->id,
            ],
            user: $customer,
        );

        return $rating;
    }
}
