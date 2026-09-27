<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ActivityLogger;
use App\Services\BookingEmailNotifier;
use App\Services\BookingNumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCustomerBooking
{
    public function __construct(
        private readonly BookingNumberGenerator $bookingNumberGenerator,
        private readonly CreateBookingInvoice $createBookingInvoice,
        private readonly BookingEmailNotifier $bookingEmailNotifier,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $customer, array $data): Booking
    {
        if (! $customer->isCustomer()) {
            throw ValidationException::withMessages([
                'customer' => 'Only customers can create bookings through this action.',
            ]);
        }

        $pricing = Pricing::query()->find($data['pricing_id'] ?? null);

        if ($pricing === null) {
            throw ValidationException::withMessages([
                'pricing_id' => 'The selected pricing is invalid.',
            ]);
        }

        $hasAvailableVehicle = Vehicle::query()
            ->where('pricing_id', $pricing->id)
            ->where('status', VehicleStatus::Available)
            ->exists();

        if (! $hasAvailableVehicle) {
            throw ValidationException::withMessages([
                'pricing_id' => 'No available fleet unit for this vehicle type right now.',
            ]);
        }

        $status = isset($data['status'])
            ? BookingStatus::from((string) $data['status'])
            : BookingStatus::Pending;

        $booking = DB::transaction(function () use ($customer, $data, $pricing, $status): Booking {
            $booking = Booking::query()->create([
                'booking_number' => $this->bookingNumberGenerator->next(),
                'customer_id' => $customer->id,
                'driver_id' => $data['driver_id'] ?? null,
                'pricing_id' => $pricing->id,
                'booking_datetime' => $data['booking_datetime'],
                'posting_date' => now('Asia/Manila')->toDateString(),
                'pickup_address' => $data['pickup_address'],
                'pickup_lat' => $data['pickup_lat'],
                'pickup_lng' => $data['pickup_lng'],
                'dropoff_address' => $data['dropoff_address'],
                'dropoff_lat' => $data['dropoff_lat'],
                'dropoff_lng' => $data['dropoff_lng'],
                'cargo_desc' => $data['cargo_desc'] ?? null,
                'additional_requirements' => $data['additional_requirements'] ?? null,
                'status' => $status,
                'is_locked' => false,
            ]);

            $this->createBookingInvoice->execute($booking);

            return $booking->fresh(['invoice', 'pricing']) ?? $booking;
        });

        $this->bookingEmailNotifier->bookingCreated($booking);

        $this->activityLogger->log(
            action: 'booking.created',
            subject: $booking,
            description: "Booking {$booking->booking_number} created.",
            properties: [
                'pricing_id' => $booking->pricing_id,
                'vehicle_type' => $booking->vehicle_type,
                'payout' => (string) ($booking->payout ?? $pricing->amount),
            ],
            user: $customer,
        );

        return $booking;
    }
}
