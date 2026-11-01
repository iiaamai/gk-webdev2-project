<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\MultiTripOfficeStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\StaffOverviewQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LifecycleQaMatrixTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array<string, mixed>> */
    private array $rows = [];

    public function test_lifecycle_qa_matrix_extras_and_gaps(): void
    {
        Storage::fake('local');

        $this->runX1OfficeRejectMultiTrip();
        $this->runX2BlockTransitCompletedWhileMultiTripPending();
        $this->runX3CustomerCancelPendingNoGatepass();
        $this->runX4DriverNotTimedIn();
        $this->runX5EirBlockedBeforeTransitCompleted();
        $this->runX6StaffKpiPhaseCounts();
        $this->runDCustomerCancelAfterGatepassBeforeAccept();
        $this->runFStartHaulWhileMultiTripPending();
        $this->runIDriverNextStepAndCustomerCallout();

        $path = storage_path('app/lifecycle-qa-matrix.json');
        file_put_contents($path, json_encode(['rows' => $this->rows], JSON_PRETTY_PRINT));

        foreach ($this->rows as $row) {
            $this->assertSame(
                'Pass',
                $row['pass'],
                ($row['scenario_id'] ?? '?').' step '.($row['step'] ?? '?').': '.($row['notes'] ?? '')
            );
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function row(
        string $scenarioId,
        int $step,
        string $phase,
        string $role,
        string $action,
        string $expected,
        string $actual,
        bool $pass,
        string $notes = '',
    ): void {
        $this->rows[] = [
            'scenario_id' => $scenarioId,
            'step' => $step,
            'phase' => $phase,
            'role' => $role,
            'action' => $action,
            'expected' => $expected,
            'actual' => $actual,
            'pass' => $pass ? 'Pass' : 'Fail',
            'notes' => $notes,
        ];
    }

    private function runX1OfficeRejectMultiTrip(): void
    {
        $customer = User::factory()->customer()->create();
        $staff = User::factory()->staff()->create();
        $driver = User::factory()->driver()->timedIn()->create();
        $pricing = Pricing::factory()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'driver_id' => $driver->id,
            'pricing_id' => $pricing->id,
            'status' => BookingStatus::ArrivedAtPickup,
            'arrived_at_pickup_at' => now(),
            'multi_trip_requested_rounds' => 3,
            'multi_trip_office_status' => MultiTripOfficeStatus::Pending,
        ]);

        $this->actingAs($staff)
            ->post(route('staff.bookings.multi-trip.reject', $booking))
            ->assertRedirect();

        $booking->refresh();
        $pass = $booking->status === BookingStatus::Cancelled;
        $this->row('X1', 1, 'At pickup', 'Staff', 'Reject multi-trip request', 'Booking cancelled', $booking->status->label(), $pass);
    }

    private function runX2BlockTransitCompletedWhileMultiTripPending(): void
    {
        $driver = User::factory()->driver()->timedIn()->create();
        $booking = Booking::factory()->create([
            'driver_id' => $driver->id,
            'status' => BookingStatus::InTransit,
            'arrived_at_pickup_at' => now(),
            'multi_trip_requested_rounds' => 2,
            'multi_trip_office_status' => MultiTripOfficeStatus::Pending,
        ]);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.transit-completed', $booking))
            ->assertForbidden();

        $booking->refresh();
        $pass = $booking->status === BookingStatus::InTransit;
        $this->row('X2', 1, 'Haul', 'Driver', 'Mark transit completed while office pending', 'Stay in haul; validation error', $booking->status->label(), $pass);
    }

    private function runX3CustomerCancelPendingNoGatepass(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Pending,
            'accepted_at' => null,
            'gatepass_path' => null,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.bookings.cancel', $booking))
            ->assertRedirect();

        $booking->refresh();
        $pass = $booking->status === BookingStatus::Cancelled;
        $this->row('X3', 1, 'Pending', 'Customer', 'Cancel before gatepass / accept', 'Cancelled', $booking->status->label(), $pass);
    }

    private function runX4DriverNotTimedIn(): void
    {
        $driver = User::factory()->driver()->create();

        $response = $this->actingAs($driver)->get(route('driver.deliveries.index'));
        $pass = $response->isRedirect();
        $this->row('X4', 1, 'Login', 'Driver', 'Open deliveries without time-in', 'Redirect to time-in gate', $pass ? 'Redirect' : 'HTTP '.$response->status(), $pass);
    }

    private function runX5EirBlockedBeforeTransitCompleted(): void
    {
        $driver = User::factory()->driver()->timedIn()->create();
        $booking = Booking::factory()->create([
            'driver_id' => $driver->id,
            'status' => BookingStatus::InTransit,
        ]);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.eir.store', $booking), [
                'eir' => UploadedFile::fake()->image('eir.jpg'),
            ])
            ->assertForbidden();

        $this->row('X5', 1, 'Haul', 'Driver', 'Upload EIR before transit completed', '403 forbidden', '403 forbidden', true);
    }

    private function runX6StaffKpiPhaseCounts(): void
    {
        Booking::query()->delete();

        $staff = User::factory()->staff()->create();
        Booking::factory()->create(['status' => BookingStatus::TransitToPickup]);
        Booking::factory()->create(['status' => BookingStatus::ArrivedAtPickup]);
        Booking::factory()->create(['status' => BookingStatus::InTransit]);
        Booking::factory()->create(['status' => BookingStatus::TransitCompleted]);

        $overview = app(StaffOverviewQuery::class)->gather();
        $toPickup = (int) ($overview['kpis']['to_pickup'] ?? 0);
        $onTrip = (int) ($overview['kpis']['on_trip'] ?? 0);

        $pass = $toPickup === 1 && $onTrip === 3;
        $this->row(
            'X6',
            1,
            'Staff home',
            'Staff',
            'KPI To pickup / On trip counts',
            'To pickup=1 (transit_to_pickup only); On trip=3 (arrived+haul+transit_completed)',
            "to_pickup={$toPickup}; on_trip={$onTrip}",
            $pass
        );

        $this->actingAs($staff)
            ->get(route('staff.home'))
            ->assertOk()
            ->assertSee('To pickup')
            ->assertSee('On trip');

        $this->row('X6', 2, 'Staff home', 'Staff', 'Home renders phase KPI labels', 'To pickup + On trip visible', 'Labels visible', true);
    }

    private function runDCustomerCancelAfterGatepassBeforeAccept(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->withGatepass()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Pending,
            'accepted_at' => null,
            'driver_id' => null,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.bookings.cancel', $booking))
            ->assertRedirect(route('customer.bookings.index'));

        $booking->refresh();
        $pass = $booking->status === BookingStatus::Cancelled;
        $this->row('D', 1, 'Pending', 'Customer', 'Cancel after gatepass, before driver accept', 'Cancelled (no accept yet)', $booking->status->label(), $pass);
    }

    private function runFStartHaulWhileMultiTripPending(): void
    {
        $driver = User::factory()->driver()->timedIn()->create();
        $booking = Booking::factory()->create([
            'driver_id' => $driver->id,
            'status' => BookingStatus::ArrivedAtPickup,
            'arrived_at_pickup_at' => now(),
            'multi_trip_requested_rounds' => 2,
            'multi_trip_office_status' => MultiTripOfficeStatus::Pending,
        ]);

        $this->actingAs($driver)
            ->patch(route('driver.deliveries.status.update', $booking), ['status' => 'in_transit'])
            ->assertRedirect();

        $booking->refresh();
        $haulOk = $booking->status === BookingStatus::InTransit;

        $this->actingAs($driver)
            ->post(route('driver.deliveries.transit-completed', $booking))
            ->assertForbidden();

        $booking->refresh();
        $blockOk = $booking->status === BookingStatus::InTransit;

        $this->row('F', 1, 'At pickup', 'Driver', 'Start haul while multi-trip office pending', 'Haul allowed', $booking->status->label(), $haulOk);
        $this->row('F', 2, 'Haul', 'Driver', 'Transit completed while still pending', 'Blocked; remain in haul', $booking->status->label(), $blockOk);
    }

    private function runIDriverNextStepAndCustomerCallout(): void
    {
        $driver = User::factory()->driver()->timedIn()->create();
        $customer = User::factory()->customer()->create();
        $pricing = Pricing::factory()->create(['vehicle_type' => 'QA I-b truck '.uniqid()]);
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => VehicleStatus::InUse,
        ]);
        $booking = Booking::factory()->withGatepass()->create([
            'customer_id' => $customer->id,
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::Accepted,
        ]);

        $this->actingAs($driver)
            ->get(route('driver.deliveries.show', $booking))
            ->assertOk()
            ->assertSee('Next step');

        $this->row('I', 1, 'Accepted', 'Driver', 'Delivery show', 'Single Next step card', 'Next step visible', true);

        $enRoute = Booking::factory()->create([
            'customer_id' => $customer->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::TransitToPickup,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.bookings.show', $enRoute))
            ->assertOk()
            ->assertSee('on the way to the pickup location', false);

        $this->row('I', 2, 'Transit to pickup', 'Customer', 'Booking detail callout', 'En-route callout text', 'Callout visible', true);
    }
}
