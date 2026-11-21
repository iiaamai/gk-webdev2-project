<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Eir;
use App\Models\Invoice;
use App\Models\Pod;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\DocumentStorage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use RuntimeException;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = User::query()->withArchived()
            ->whereIn('email', [SeedAccounts::CUSTOMER_EMAIL, SeedAccounts::CUSTOMER_TWO_EMAIL])
            ->get()
            ->keyBy('email');

        $driver = User::query()->withArchived()->where('email', SeedAccounts::DRIVER_EMAIL)->firstOrFail();
        $driverTwo = User::query()->withArchived()->where('email', SeedAccounts::DRIVER_TWO_EMAIL)->firstOrFail();

        $sixWheeler = Vehicle::query()->withArchived()->where('plate_number', 'ISU-4X2-001')->firstOrFail();
        $tenWheeler = Vehicle::query()->withArchived()->where('plate_number', 'ISU-6X4-001')->firstOrFail();
        $economyTruck = Vehicle::query()->withArchived()->where('plate_number', 'ECO-6W-003')->firstOrFail();
        $economyTruckTwo = Vehicle::query()->withArchived()->where('plate_number', 'ECO-6W-002')->firstOrFail();
        $sixWheelerTwo = Vehicle::query()->withArchived()->where('plate_number', 'ISU-4X2-002')->firstOrFail();

        $pricingByType = Pricing::query()->pluck('id', 'vehicle_type');
        $amountByPricingId = Pricing::query()->pluck('amount', 'id');
        $tz = 'Asia/Manila';
        $year = now($tz)->year;

        $defaults = [
            'pickup_address' => 'Makati City, Metro Manila',
            'pickup_lat' => 14.5547,
            'pickup_lng' => 121.0244,
            'dropoff_address' => 'Quezon City, Metro Manila',
            'dropoff_lat' => 14.6760,
            'dropoff_lng' => 121.0437,
        ];

        $samples = [
            [
                'suffix' => '0001',
                'customer_email' => SeedAccounts::CUSTOMER_EMAIL,
                'pricing_id' => $pricingByType['6-wheeler'],
                'vehicle_id' => null,
                'driver_id' => null,
                'status' => BookingStatus::Pending,
                'is_locked' => false,
                'accepted_at' => null,
                'gatepass' => false,
                'cargo_desc' => 'Palletized office furniture — Ayala CBD warehouse',
                'pickup_port_number' => 'MICT-14',
                'pickup_container_number' => 'TCLU4829103',
                'cargo_kg' => 12000,
                'booking_offset_days' => 0,
                'with_invoice' => InvoiceStatus::Unpaid,
            ],
            [
                'suffix' => '0002',
                'customer_email' => SeedAccounts::CUSTOMER_EMAIL,
                'pricing_id' => $pricingByType['10-wheeler'],
                'vehicle_id' => null,
                'driver_id' => null,
                'status' => BookingStatus::Pending,
                'is_locked' => false,
                'accepted_at' => null,
                'gatepass' => true,
                'cargo_desc' => 'Steel coils — gatepass issued, awaiting driver',
                'pickup_port_number' => 'SBMA-07',
                'pickup_container_number' => 'FCIU7734001',
                'cargo_kg' => 52000,
                'booking_offset_days' => 0,
                'pickup_address' => 'Port Area, Manila',
                'dropoff_address' => 'Caloocan City, Metro Manila',
                'with_invoice' => InvoiceStatus::Unpaid,
            ],
            [
                'suffix' => '0003',
                'customer_email' => SeedAccounts::CUSTOMER_EMAIL,
                'pricing_id' => $pricingByType['6-wheeler'],
                'vehicle_id' => $sixWheeler->id,
                'driver_id' => $driver->id,
                'status' => BookingStatus::Accepted,
                'is_locked' => true,
                'accepted_at_hours' => -2,
                'gatepass' => true,
                'cargo_desc' => 'Consumer electronics — confirmed haul',
                'pickup_port_number' => 'MICT-22',
                'pickup_container_number' => 'MSKU1192844',
                'cargo_kg' => 18500,
                'booking_offset_days' => 1,
                'lock_vehicle' => $sixWheeler,
                'with_invoice' => InvoiceStatus::Unpaid,
            ],
            [
                'suffix' => '0004',
                'customer_email' => SeedAccounts::CUSTOMER_EMAIL,
                'pricing_id' => $pricingByType['10-wheeler'],
                'vehicle_id' => $tenWheeler->id,
                'driver_id' => $driverTwo->id,
                'status' => BookingStatus::TransitToPickup,
                'is_locked' => true,
                'accepted_at_days' => -1,
                'gatepass' => true,
                'cargo_desc' => 'Cement bags — driver en route to pickup',
                'pickup_port_number' => 'MICT-03',
                'pickup_container_number' => 'CMAU8821009',
                'cargo_kg' => 60000,
                'booking_offset_days' => 1,
                'pickup_address' => 'Taguig City, Metro Manila',
                'dropoff_address' => 'Pasig City, Metro Manila',
                'lock_vehicle' => $tenWheeler,
                'with_invoice' => InvoiceStatus::Unpaid,
            ],
            [
                'suffix' => '0005',
                'customer_email' => SeedAccounts::CUSTOMER_EMAIL,
                'pricing_id' => $pricingByType['6-wheeler (economy)'],
                'vehicle_id' => $economyTruck->id,
                'driver_id' => $driver->id,
                'status' => BookingStatus::Completed,
                'is_locked' => true,
                'accepted_at_days' => -3,
                'gatepass' => true,
                'cargo_desc' => 'Grocery dry goods — delivered with EIR and POD',
                'pickup_port_number' => 'SBMA-02',
                'pickup_container_number' => 'HALU5543210',
                'cargo_kg' => 15000,
                'booking_offset_days' => -2,
                'with_eir_pod' => true,
                'with_invoice' => InvoiceStatus::Paid,
            ],
            [
                'suffix' => '0006',
                'customer_email' => SeedAccounts::CUSTOMER_EMAIL,
                'pricing_id' => $pricingByType['10-wheeler'],
                'vehicle_id' => null,
                'driver_id' => null,
                'status' => BookingStatus::Cancelled,
                'is_locked' => false,
                'accepted_at' => null,
                'gatepass' => false,
                'cargo_desc' => 'Cancelled — customer rescheduled import slot',
                'pickup_port_number' => 'MICT-18',
                'pickup_container_number' => 'TGHU3301922',
                'cargo_kg' => 48000,
                'booking_offset_days' => -5,
            ],
            [
                'suffix' => '0007',
                'customer_email' => SeedAccounts::CUSTOMER_TWO_EMAIL,
                'pricing_id' => $pricingByType['6-wheeler'],
                'vehicle_id' => $sixWheelerTwo->id,
                'driver_id' => $driver->id,
                'status' => BookingStatus::Completed,
                'is_locked' => false,
                'accepted_at_days' => -42,
                'gatepass' => true,
                'cargo_desc' => 'Rice sacks — completed last month, invoice paid',
                'pickup_port_number' => 'MICT-11',
                'pickup_container_number' => 'OOLU9012231',
                'cargo_kg' => 22000,
                'booking_offset_days' => -40,
                'pickup_address' => 'Navotas, Metro Manila',
                'dropoff_address' => 'Valenzuela City, Metro Manila',
                'with_eir_pod' => true,
                'with_invoice' => InvoiceStatus::Paid,
            ],
            [
                'suffix' => '0008',
                'customer_email' => SeedAccounts::CUSTOMER_TWO_EMAIL,
                'pricing_id' => $pricingByType['10-wheeler'],
                'vehicle_id' => null,
                'driver_id' => $driverTwo->id,
                'status' => BookingStatus::Completed,
                'is_locked' => false,
                'accepted_at_days' => -68,
                'gatepass' => true,
                'cargo_desc' => 'Industrial resin — completed prior month, unpaid invoice',
                'pickup_port_number' => 'SBMA-04',
                'pickup_container_number' => 'GESU4412098',
                'cargo_kg' => 65000,
                'booking_offset_days' => -65,
                'pickup_address' => 'Las Piñas City, Metro Manila',
                'dropoff_address' => 'Muntinlupa City, Metro Manila',
                'with_invoice' => InvoiceStatus::Unpaid,
            ],
            [
                'suffix' => '0009',
                'customer_email' => SeedAccounts::CUSTOMER_EMAIL,
                'pricing_id' => $pricingByType['6-wheeler (economy)'],
                'vehicle_id' => $economyTruckTwo->id,
                'driver_id' => $driver->id,
                'status' => BookingStatus::Completed,
                'is_locked' => false,
                'accepted_at_days' => -18,
                'gatepass' => true,
                'cargo_desc' => 'Packaging materials — closed trip, paid',
                'pickup_port_number' => 'MICT-06',
                'pickup_container_number' => 'TRHU5521004',
                'cargo_kg' => 14000,
                'booking_offset_days' => -16,
                'with_invoice' => InvoiceStatus::Paid,
            ],
        ];

        foreach ($samples as $row) {
            $customerEmail = $row['customer_email'];
            $suffix = $row['suffix'];
            $bookingOffsetDays = $row['booking_offset_days'];
            $gatepass = $row['gatepass'];
            $lockVehicle = $row['lock_vehicle'] ?? null;
            $withEirPod = $row['with_eir_pod'] ?? false;
            $withInvoice = $row['with_invoice'] ?? null;

            $bookingNumber = "GK-{$year}-{$suffix}";
            $anchor = now($tz)->addDays($bookingOffsetDays);

            $acceptedAt = $row['accepted_at'] ?? null;
            if ($acceptedAt === null && isset($row['accepted_at_days'])) {
                $acceptedAt = now($tz)->addDays($row['accepted_at_days']);
            }
            if ($acceptedAt === null && isset($row['accepted_at_hours'])) {
                $acceptedAt = now($tz)->addHours($row['accepted_at_hours']);
            }

            $gatepassPath = null;
            if ($gatepass) {
                $gatepassPath = $this->storeGatepassFixture($bookingNumber);
            }

            $payload = [
                'booking_number' => $bookingNumber,
                'customer_id' => $customers[$customerEmail]->id,
                'pricing_id' => $row['pricing_id'],
                'vehicle_id' => $row['vehicle_id'],
                'driver_id' => $row['driver_id'],
                'status' => $row['status'],
                'is_locked' => $row['is_locked'],
                'accepted_at' => $acceptedAt,
                'gatepass_path' => $gatepassPath,
                'cargo_desc' => $row['cargo_desc'],
                'pickup_port_number' => $row['pickup_port_number'],
                'pickup_container_number' => $row['pickup_container_number'],
                'cargo_kg' => $row['cargo_kg'],
                'booking_datetime' => $anchor->copy()->addDay(),
                'posting_date' => $anchor->toDateString(),
                'pickup_address' => $row['pickup_address'] ?? $defaults['pickup_address'],
                'pickup_lat' => $defaults['pickup_lat'],
                'pickup_lng' => $defaults['pickup_lng'],
                'dropoff_address' => $row['dropoff_address'] ?? $defaults['dropoff_address'],
                'dropoff_lat' => $defaults['dropoff_lat'],
                'dropoff_lng' => $defaults['dropoff_lng'],
            ];

            $booking = Booking::query()->withArchived()->updateOrCreate(
                ['booking_number' => $bookingNumber],
                $payload,
            );

            $booking->forceFill([
                'created_at' => $anchor,
                'updated_at' => $anchor,
            ])->saveQuietly();

            if ($lockVehicle instanceof Vehicle) {
                $lockVehicle->update(['status' => VehicleStatus::InUse]);
            }

            if ($withEirPod) {
                $docAt = $anchor->copy()->addDay();
                $eirPath = "bookings/{$bookingNumber}/eir.jpg";
                $podPaths = [
                    "bookings/{$bookingNumber}/pod-1.jpg",
                    "bookings/{$bookingNumber}/pod-2.jpg",
                ];

                Eir::query()->withArchived()->updateOrCreate(
                    ['booking_id' => $booking->id],
                    [
                        'eir_path' => $eirPath,
                        'uploaded_at' => $docAt,
                    ],
                );

                Pod::query()->withArchived()->updateOrCreate(
                    ['booking_id' => $booking->id],
                    [
                        'photo_paths' => $podPaths,
                        'captured_at' => $docAt,
                    ],
                );
            }

            if ($withInvoice instanceof InvoiceStatus) {
                $issuedAt = $anchor->copy()->addDay();
                Invoice::query()->withArchived()->updateOrCreate(
                    ['booking_id' => $booking->id],
                    [
                        'amount' => $amountByPricingId[$booking->pricing_id] ?? 0,
                        'status' => $withInvoice,
                        'issued_at' => $issuedAt,
                        'paid_at' => $withInvoice === InvoiceStatus::Paid
                            ? $issuedAt->copy()->addDay()
                            : null,
                    ],
                );
            }
        }
    }

    private function storeGatepassFixture(string $bookingNumber): string
    {
        $fixture = database_path('seeders/fixtures/sample-gatepass.png');

        if (! File::isReadable($fixture)) {
            throw new RuntimeException('Missing gatepass seed fixture: '.$fixture);
        }

        $path = 'bookings/'.$bookingNumber.'/gatepass.png';
        DocumentStorage::disk()->put($path, File::get($fixture));

        return $path;
    }
}
