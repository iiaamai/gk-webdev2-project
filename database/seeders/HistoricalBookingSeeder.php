<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\DocumentStorage;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use RuntimeException;

class HistoricalBookingSeeder extends Seeder
{
    private const HISTORY_START_YEAR = 2020;

    /**
     * @var array<int, int>
     */
    private array $sequenceByYear = [];

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

        $vehicles = [
            Vehicle::query()->withArchived()->where('plate_number', 'ISU-4X2-001')->firstOrFail(),
            Vehicle::query()->withArchived()->where('plate_number', 'ISU-6X4-001')->firstOrFail(),
            Vehicle::query()->withArchived()->where('plate_number', 'ECO-6W-003')->firstOrFail(),
        ];

        $pricingByType = Pricing::query()->pluck('id', 'vehicle_type');
        $amountByPricingId = Pricing::query()->pluck('amount', 'id')->all();

        $pricingRotation = [
            $pricingByType['6-wheeler'],
            $pricingByType['10-wheeler'],
            $pricingByType['6-wheeler (economy)'],
        ];

        $cargoDescriptions = [
            'Palletized canned goods — North Harbor release',
            'Building materials — regional depot transfer',
            'Appliance cartons — bonded warehouse pull',
            'Fertilizer sacks — agricultural supplier',
            'Beverage cases — distributor run',
            'Paper rolls — print plant delivery',
            'Ceramic tiles — showroom stock',
            'Auto parts — aftermarket warehouse',
            'Textile bales — export forwarding',
            'Plastic resin — industrial park',
        ];

        $tz = 'Asia/Manila';
        $endYear = (int) now($tz)->year;

        $tripIndex = 0;

        for ($year = self::HISTORY_START_YEAR; $year <= $endYear; $year++) {
            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $primaryMonth = ($quarter - 1) * 3 + 2;
                $this->seedCompletedTrip(
                    year: $year,
                    month: $primaryMonth,
                    day: 12,
                    customers: $customers,
                    drivers: [$driver, $driverTwo],
                    vehicles: $vehicles,
                    pricingRotation: $pricingRotation,
                    amountByPricingId: $amountByPricingId,
                    cargoDescriptions: $cargoDescriptions,
                    tripIndex: $tripIndex,
                    tz: $tz,
                );
                $tripIndex++;

                if ($quarter % 2 === 0) {
                    $secondaryMonth = ($quarter - 1) * 3 + 3;
                    $this->seedCompletedTrip(
                        year: $year,
                        month: $secondaryMonth,
                        day: 18,
                        customers: $customers,
                        drivers: [$driver, $driverTwo],
                        vehicles: $vehicles,
                        pricingRotation: $pricingRotation,
                        amountByPricingId: $amountByPricingId,
                        cargoDescriptions: $cargoDescriptions,
                        tripIndex: $tripIndex,
                        tz: $tz,
                    );
                    $tripIndex++;
                }
            }

            if ($year >= 2025) {
                for ($month = 1; $month <= 12; $month++) {
                    $this->seedCompletedTrip(
                        year: $year,
                        month: $month,
                        day: 22,
                        customers: $customers,
                        drivers: [$driver, $driverTwo],
                        vehicles: $vehicles,
                        pricingRotation: $pricingRotation,
                        amountByPricingId: $amountByPricingId,
                        cargoDescriptions: $cargoDescriptions,
                        tripIndex: $tripIndex,
                        tz: $tz,
                    );
                    $tripIndex++;
                }
            }
        }
    }

    /**
     * @param  Collection<string, User>  $customers
     * @param  array<int, User>  $drivers
     * @param  array<int, Vehicle>  $vehicles
     * @param  array<int, int|string>  $pricingRotation
     * @param  array<int|string, mixed>  $amountByPricingId
     * @param  array<int, string>  $cargoDescriptions
     */
    private function seedCompletedTrip(
        int $year,
        int $month,
        int $day,
        $customers,
        array $drivers,
        array $vehicles,
        array $pricingRotation,
        array $amountByPricingId,
        array $cargoDescriptions,
        int $tripIndex,
        string $tz,
    ): void {
        $acceptedAt = Carbon::create($year, $month, $day, 9, 30, 0, $tz);
        $anchor = $acceptedAt->copy()->subDays(2);

        $customerEmail = $tripIndex % 2 === 0
            ? SeedAccounts::CUSTOMER_EMAIL
            : SeedAccounts::CUSTOMER_TWO_EMAIL;

        $pricingId = $pricingRotation[$tripIndex % count($pricingRotation)];
        $driver = $drivers[$tripIndex % count($drivers)];
        $vehicle = $vehicles[$tripIndex % count($vehicles)];

        $bookingNumber = $this->nextHistoricalBookingNumber($year);
        $gatepassPath = $this->storeGatepassFixture($bookingNumber);

        $defaults = [
            'pickup_address' => 'Makati City, Metro Manila',
            'pickup_lat' => 14.5547,
            'pickup_lng' => 121.0244,
            'dropoff_address' => 'Quezon City, Metro Manila',
            'dropoff_lat' => 14.6760,
            'dropoff_lng' => 121.0437,
        ];

        $booking = Booking::query()->withArchived()->updateOrCreate(
            ['booking_number' => $bookingNumber],
            [
                'customer_id' => $customers[$customerEmail]->id,
                'pricing_id' => $pricingId,
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'status' => BookingStatus::Completed,
                'is_locked' => false,
                'accepted_at' => $acceptedAt,
                'gatepass_path' => $gatepassPath,
                'cargo_desc' => $cargoDescriptions[$tripIndex % count($cargoDescriptions)],
                'pickup_port_number' => sprintf('MICT-%02d', ($tripIndex % 28) + 1),
                'pickup_container_number' => sprintf('HISTU%07d', 1000000 + $tripIndex),
                'cargo_kg' => 14000 + ($tripIndex % 5) * 3000,
                'booking_datetime' => $anchor->copy()->addDay(),
                'posting_date' => $anchor->toDateString(),
                'pickup_address' => $defaults['pickup_address'],
                'pickup_lat' => $defaults['pickup_lat'],
                'pickup_lng' => $defaults['pickup_lng'],
                'dropoff_address' => $defaults['dropoff_address'],
                'dropoff_lat' => $defaults['dropoff_lat'],
                'dropoff_lng' => $defaults['dropoff_lng'],
            ],
        );

        $booking->forceFill([
            'created_at' => $anchor,
            'updated_at' => $acceptedAt,
        ])->saveQuietly();

        $invoiceStatus = ($year + $month + $tripIndex) % 10 < 7
            ? InvoiceStatus::Paid
            : InvoiceStatus::Unpaid;

        $issuedAt = $acceptedAt->copy()->addHours(6);

        Invoice::query()->withArchived()->updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'amount' => $amountByPricingId[$pricingId] ?? 0,
                'status' => $invoiceStatus,
                'issued_at' => $issuedAt,
                'paid_at' => $invoiceStatus === InvoiceStatus::Paid
                    ? $issuedAt->copy()->addDay()
                    : null,
            ],
        );
    }

    private function nextHistoricalBookingNumber(int $year): string
    {
        if (! isset($this->sequenceByYear[$year])) {
            $next = $year === (int) now('Asia/Manila')->year ? 1010 : 1000;
        } else {
            $next = $this->sequenceByYear[$year] + 1;
        }

        $this->sequenceByYear[$year] = $next;

        return sprintf('GK-%d-%04d', $year, $next);
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
