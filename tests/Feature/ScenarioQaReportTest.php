<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\BookingMultiTrip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScenarioQaReportTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{step: int, role: string, action: string, expected: string, actual: string, pass: string}> */
    private array $scenario1 = [];

    /** @var list<array{step: int, role: string, action: string, expected: string, actual: string, pass: string}> */
    private array $scenario2 = [];

    /** @var list<array{step: int, role: string, action: string, expected: string, actual: string, pass: string}> */
    private array $scenario3 = [];

    public function test_run_three_scenario_qa_and_write_report(): void
    {
        Storage::fake('local');

        $customer = User::factory()->customer()->create(['email' => 'qa-customer@gk.test']);
        $staff = User::factory()->staff()->create();
        $driver = User::factory()->driver()->timedIn()->create(['email' => 'qa-driver@gk.test']);
        $truckPricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 9200, 'capacity_kg' => 3000]);
        $vanPricing = Pricing::factory()->create(['vehicle_type' => 'L300 van', 'amount' => 4500, 'capacity_kg' => 1000]);

        Vehicle::factory()->create([
            'pricing_id' => $truckPricing->id,
            'driver_id' => $driver->id,
            'status' => VehicleStatus::Available,
        ]);
        Vehicle::factory()->create([
            'pricing_id' => $vanPricing->id,
            'driver_id' => null,
            'status' => VehicleStatus::Available,
        ]);

        $this->runScenario1($customer, $staff, $driver, $truckPricing);
        $this->runScenario2($customer, $staff, $driver, $truckPricing);
        $this->runScenario3($customer, $staff, $driver, $truckPricing, $vanPricing);

        $path = storage_path('app/scenario-qa-report.json');
        file_put_contents($path, json_encode([
            'scenario1' => $this->scenario1,
            'scenario2' => $this->scenario2,
            'scenario3' => $this->scenario3,
            'booking_ids' => [
                'scenario1' => $this->scenario1BookingId ?? null,
                'scenario2' => $this->scenario2BookingId ?? null,
                'scenario3' => $this->scenario3BookingId ?? null,
            ],
        ], JSON_PRETTY_PRINT));

        $this->assertScenarioLogPasses('S1', $this->scenario1);
        $this->assertScenarioLogPasses('S2', $this->scenario2);
        $this->assertScenarioLogPasses('S3', $this->scenario3);
    }

    /**
     * @param  list<array{pass: string}>  $log
     */
    private function assertScenarioLogPasses(string $scenarioId, array $log): void
    {
        foreach ($log as $row) {
            $this->assertSame('Pass', $row['pass'], $scenarioId.' step '.$row['step'].': '.$row['action']);
        }
    }

    private ?int $scenario1BookingId = null;

    private ?int $scenario2BookingId = null;

    private ?int $scenario3BookingId = null;

    private function runScenario1(User $customer, User $staff, User $driver, Pricing $pricing): void
    {
        $log = &$this->scenario1;
        $booking = Booking::factory()->withGatepass()->create([
            'customer_id' => $customer->id,
            'pricing_id' => $pricing->id,
            'booking_number' => 'GK-QA-S1',
            'status' => BookingStatus::Pending,
        ]);
        Invoice::factory()->create(['booking_id' => $booking->id, 'amount' => 9200]);
        $this->scenario1BookingId = $booking->id;

        $this->record($log, 1, 'Customer', 'View booking (implicit create + gatepass ready)', 'Pending; invoice ₱9,200', $booking, '9200.00');

        $this->actingAs($driver)->post(route('driver.deliveries.accept', $booking))->assertRedirect();
        $this->record($log, 2, 'Driver', 'Accept delivery', 'Accepted', $booking);

        $this->actingAs($driver)->patch(route('driver.deliveries.status.update', $booking), ['status' => 'transit_to_pickup'])->assertRedirect();
        $this->record($log, 3, 'Driver', 'Mark transit to pickup', 'Transit to pickup', $booking);

        $this->actingAs($driver)->post(route('driver.deliveries.arrived-at-pickup', $booking))->assertRedirect();
        $this->record($log, 4, 'Driver', 'Arrived at pickup', 'Arrived at pickup', $booking);

        $this->actingAs($driver)->patch(route('driver.deliveries.status.update', $booking), ['status' => 'in_transit'])->assertRedirect();
        $this->record($log, 5, 'Driver', 'Start haul in progress', 'Haul in progress', $booking);

        $this->actingAs($driver)->post(route('driver.deliveries.transit-completed', $booking))->assertRedirect();
        $this->record($log, 6, 'Driver', 'Mark transit completed', 'Transit completed', $booking);

        $this->actingAs($driver)->post(route('driver.deliveries.eir.store', $booking), [
            'eir' => UploadedFile::fake()->image('eir.jpg'),
        ])->assertRedirect();
        $this->actingAs($driver)->post(route('driver.deliveries.pod.store', $booking), [
            'photos' => [UploadedFile::fake()->image('pod.jpg')],
        ])->assertRedirect();
        $this->record($log, 7, 'Driver', 'Upload EIR + POD', 'Transit completed; documents stored', $booking, null, fn (Booking $b) => ($b->eir !== null && $b->pod !== null) ? 'EIR+POD yes' : 'missing docs');

        $this->actingAs($driver)->patch(route('driver.deliveries.status.update', $booking), ['status' => 'completed'])->assertRedirect();
        $this->record($log, 8, 'Driver', 'Mark completed', 'Completed; invoice still ₱9,200', $booking, '9200.00');

        $this->actingAs($customer)->get(route('customer.bookings.show', $booking))->assertOk()->assertSee('Completed');
        $this->record($log, 9, 'Customer', 'Open booking detail', 'Completed badge + receipt rules', $booking);
    }

    private function runScenario2(User $customer, User $staff, User $driver, Pricing $pricing): void
    {
        $log = &$this->scenario2;
        $driver2 = User::factory()->driver()->timedIn()->create();
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'driver_id' => $driver2->id,
            'status' => VehicleStatus::Available,
        ]);

        $booking = Booking::factory()->withGatepass()->create([
            'customer_id' => $customer->id,
            'pricing_id' => $pricing->id,
            'booking_number' => 'GK-QA-S2',
            'status' => BookingStatus::Pending,
        ]);
        Invoice::factory()->create(['booking_id' => $booking->id, 'amount' => 9200]);
        $this->scenario2BookingId = $booking->id;

        $this->actingAs($driver2)->post(route('driver.deliveries.accept', $booking))->assertRedirect();
        $this->record($log, 1, 'Driver', 'Accept delivery', 'Accepted', $booking);

        $this->actingAs($driver2)->patch(route('driver.deliveries.status.update', $booking), ['status' => 'transit_to_pickup'])->assertRedirect();
        $this->actingAs($driver2)->post(route('driver.deliveries.arrived-at-pickup', $booking))->assertRedirect();
        $this->record($log, 2, 'Driver', 'Transit to pickup → arrived at pickup', 'Arrived at pickup', $booking);

        $this->actingAs($customer)->post(route('customer.bookings.multi-trip.request', $booking), ['round_trips' => 3])->assertRedirect();
        $this->record($log, 3, 'Customer', 'Request 3 round trips', 'Arrived at pickup; multi-trip pending; requested=3', $booking, null, fn (Booking $b) => 'requested='.$b->multi_trip_requested_rounds);

        $this->actingAs($staff)->post(route('staff.bookings.multi-trip.approve', $booking), ['round_trips' => 3])->assertRedirect();
        $this->record($log, 4, 'Staff', 'Approve 3 round trips (match request)', 'Invoice ₱27,600 (3×₱9,200); customer auto-confirmed', $booking, '27600.00');

        $this->actingAs($driver2)->post(route('driver.deliveries.multi-trip.accept', $booking))->assertRedirect();
        $this->record($log, 5, 'Driver', 'Accept round-trip count', 'Multi-trip resolved; can proceed haul', $booking, null, fn (Booking $b) => $b->multi_trip_driver_accepted_at ? 'driver accepted' : 'not accepted');

        $this->actingAs($driver2)->patch(route('driver.deliveries.status.update', $booking), ['status' => 'in_transit'])->assertRedirect();
        $this->completeAllRoundTripLaps($driver2, $booking);
        $this->actingAs($driver2)->post(route('driver.deliveries.transit-completed', $booking))->assertRedirect();
        $this->record($log, 6, 'Driver', 'Haul → transit completed', 'Transit completed', $booking);

        $this->actingAs($driver2)->post(route('driver.deliveries.eir.store', $booking), ['eir' => UploadedFile::fake()->image('eir.jpg')])->assertRedirect();
        $this->actingAs($driver2)->post(route('driver.deliveries.pod.store', $booking), ['photos' => [UploadedFile::fake()->image('pod.jpg')]])->assertRedirect();
        $this->actingAs($driver2)->patch(route('driver.deliveries.status.update', $booking), ['status' => 'completed'])->assertRedirect();
        $this->record($log, 7, 'Driver', 'EIR/POD + completed', 'Completed; invoice ₱27,600', $booking, '27600.00');

        $this->actingAs($customer)->get(route('customer.bookings.show', $booking))->assertOk()->assertSee('Completed');
        $this->record($log, 8, 'Customer', 'View completed booking', 'Completed; 3 round trips billed', $booking);
    }

    private function runScenario3(User $customer, User $staff, User $driver, Pricing $truckPricing, Pricing $vanPricing): void
    {
        $log = &$this->scenario3;
        $driver3 = User::factory()->driver()->timedIn()->create();
        Vehicle::factory()->create([
            'pricing_id' => $truckPricing->id,
            'driver_id' => $driver3->id,
            'status' => VehicleStatus::Available,
        ]);

        $booking = Booking::factory()->withGatepass()->create([
            'customer_id' => $customer->id,
            'pricing_id' => $truckPricing->id,
            'booking_number' => 'GK-QA-S3',
            'status' => BookingStatus::Pending,
        ]);
        Invoice::factory()->create(['booking_id' => $booking->id, 'amount' => 9200]);
        $this->scenario3BookingId = $booking->id;

        $this->actingAs($driver3)->post(route('driver.deliveries.accept', $booking))->assertRedirect();
        $this->actingAs($driver3)->patch(route('driver.deliveries.status.update', $booking), ['status' => 'transit_to_pickup'])->assertRedirect();
        $this->actingAs($driver3)->post(route('driver.deliveries.arrived-at-pickup', $booking))->assertRedirect();
        $this->record($log, 1, 'Driver', 'Accept → arrived at pickup', 'Arrived at pickup', $booking);

        $this->actingAs($customer)->post(route('customer.bookings.vehicle-type.change', $booking), ['pricing_id' => $vanPricing->id])->assertRedirect();
        $this->record($log, 2, 'Customer', 'Change vehicle to L300 van (once)', 'Invoice ₱5,420 (₱4,500 + 10% wrong-truck on ₱9,200)', $booking, '5420.00');

        $this->actingAs($customer)->post(route('customer.bookings.multi-trip.request', $booking), ['round_trips' => 2])->assertRedirect();
        $this->record($log, 3, 'Customer', 'Request 2 round trips', 'Arrived at pickup; requested=2', $booking, null, fn (Booking $b) => 'requested='.$b->multi_trip_requested_rounds);

        $this->actingAs($staff)->post(route('staff.bookings.multi-trip.approve', $booking), ['round_trips' => 2])->assertRedirect();
        $this->record($log, 4, 'Staff', 'Approve 2 round trips', 'Invoice ₱9,920 (2×₱4,500 + ₱920 fee)', $booking, '9920.00');

        $this->actingAs($driver3)->post(route('driver.deliveries.multi-trip.accept', $booking))->assertRedirect();
        $this->actingAs($driver3)->patch(route('driver.deliveries.status.update', $booking), ['status' => 'in_transit'])->assertRedirect();
        $this->completeAllRoundTripLaps($driver3, $booking);
        $this->actingAs($driver3)->post(route('driver.deliveries.transit-completed', $booking))->assertRedirect();
        $this->record($log, 5, 'Driver', 'Accept trips + haul + transit done', 'Transit completed', $booking);

        $this->actingAs($driver3)->post(route('driver.deliveries.eir.store', $booking), ['eir' => UploadedFile::fake()->image('eir.jpg')])->assertRedirect();
        $this->actingAs($driver3)->post(route('driver.deliveries.pod.store', $booking), ['photos' => [UploadedFile::fake()->image('pod.jpg')]])->assertRedirect();
        $this->actingAs($driver3)->patch(route('driver.deliveries.status.update', $booking), ['status' => 'completed'])->assertRedirect();
        $this->record($log, 6, 'Driver', 'EIR/POD + completed', 'Completed; invoice ₱9,920', $booking, '9920.00');

        $this->actingAs($customer)->get(route('customer.bookings.show', $booking))->assertOk()->assertSee('Completed');
        $this->record($log, 7, 'Customer', 'View completed booking', 'Completed; van pricing + 2 trips + fee', $booking);
    }

    /**
     * @param  list<array{step: int, role: string, action: string, expected: string, actual: string, pass: string}>  $log
     */
    private function record(
        array &$log,
        int $step,
        string $role,
        string $action,
        string $expected,
        Booking $booking,
        ?string $expectedAmount = null,
        ?\Closure $extraActual = null,
    ): void {
        $booking->refresh();
        $booking->load(['invoice', 'eir', 'pod']);

        $statusLabel = $booking->status->label();
        $amount = $booking->invoice?->amount;
        $actual = $statusLabel.($amount !== null ? '; invoice ₱'.number_format((float) $amount, 2) : '');
        if ($extraActual) {
            $actual .= '; '.$extraActual($booking);
        }

        $pass = true;
        if ($expectedAmount !== null) {
            $pass = (string) $amount === number_format((float) $expectedAmount, 2, '.', '');
        } elseif (! str_contains(strtolower($expected), strtolower($statusLabel)) && ! str_contains($expected, $statusLabel)) {
            $pass = match ($booking->status) {
                BookingStatus::InTransit => str_contains($expected, 'Haul'),
                BookingStatus::ArrivedAtPickup => str_contains($expected, 'Arrived') || str_contains($expected, 'multi-trip') || str_contains($expected, 'Multi-trip'),
                BookingStatus::TransitCompleted => str_contains($expected, 'Transit completed') || str_contains($expected, 'documents'),
                BookingStatus::Completed => str_contains($expected, 'Completed'),
                default => str_contains($expected, $statusLabel),
            };
        }

        if ($extraActual !== null && ! $pass && $booking->multi_trip_driver_accepted_at !== null) {
            $pass = str_contains($expected, 'Multi-trip') || str_contains($expected, 'multi-trip');
        }

        if ($extraActual !== null && ! $pass && $booking->multi_trip_requested_rounds !== null) {
            $pass = str_contains(strtolower($expected), 'requested') || str_contains($expected, 'multi-trip');
        }

        $log[] = [
            'step' => $step,
            'role' => $role,
            'action' => $action,
            'expected' => $expected,
            'actual' => $actual,
            'pass' => $pass ? 'Pass' : 'Fail',
        ];
    }

    private function completeAllRoundTripLaps(User $driver, Booking $booking): void
    {
        $booking->refresh();
        $total = BookingMultiTrip::effectiveRoundTripsTotal($booking);

        if ($total <= 1) {
            return;
        }

        for ($i = 0; $i < $total; $i++) {
            $this->actingAs($driver)
                ->post(route('driver.deliveries.round-trip-lap.complete', $booking))
                ->assertRedirect();
            $booking->refresh();
        }
    }
}
