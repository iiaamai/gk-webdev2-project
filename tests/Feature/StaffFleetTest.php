<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffFleetTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_and_update_status_and_driver_without_changing_details(): void
    {
        $staff = User::factory()->staff()->create();
        $pricing = Pricing::factory()->create();
        $driver = User::factory()->driver()->create();
        $vehicle = Vehicle::factory()->create([
            'plate_number' => 'STF-0001',
            'brand' => 'Isuzu',
            'color' => 'White',
            'pricing_id' => $pricing->id,
            'status' => VehicleStatus::Available,
            'driver_id' => null,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.fleet.index'))
            ->assertOk()
            ->assertSee('STF-0001')
            ->assertDontSee('Add vehicle', false)
            ->assertDontSee('Archive', false);

        $this->actingAs($staff)
            ->get(route('staff.fleet.edit', $vehicle))
            ->assertOk()
            ->assertSee('view only for staff', false);

        $this->actingAs($staff)
            ->put(route('staff.fleet.update', $vehicle), [
                'plate_number' => 'HACK-9999',
                'brand' => 'Hacked',
                'color' => 'Neon',
                'pricing_id' => $pricing->id,
                'status' => VehicleStatus::Maintenance->value,
                'driver_id' => $driver->id,
            ])
            ->assertRedirect(route('staff.fleet.index'));

        $vehicle->refresh();
        $this->assertSame('STF-0001', $vehicle->plate_number);
        $this->assertSame('Isuzu', $vehicle->brand);
        $this->assertSame('White', $vehicle->color);
        $this->assertSame(VehicleStatus::Maintenance, $vehicle->status);
        $this->assertSame($driver->id, $vehicle->driver_id);
    }

    public function test_staff_cannot_update_in_use_vehicle(): void
    {
        $staff = User::factory()->staff()->create();
        $pricing = Pricing::factory()->create();
        $driver = User::factory()->driver()->create();
        $vehicle = Vehicle::factory()->create([
            'plate_number' => 'INUSE-01',
            'brand' => 'Fuso',
            'color' => 'Blue',
            'pricing_id' => $pricing->id,
            'status' => VehicleStatus::InUse,
            'driver_id' => $driver->id,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.fleet.edit', $vehicle))
            ->assertOk()
            ->assertSee('in use', false)
            ->assertDontSee('Save changes', false);

        $this->actingAs($staff)
            ->put(route('staff.fleet.update', $vehicle), [
                'status' => VehicleStatus::Available->value,
                'driver_id' => null,
            ])
            ->assertForbidden();

        $vehicle->refresh();
        $this->assertSame(VehicleStatus::InUse, $vehicle->status);
        $this->assertSame($driver->id, $vehicle->driver_id);
        $this->assertSame('INUSE-01', $vehicle->plate_number);
    }

    public function test_staff_can_set_status_to_in_use_when_not_currently_in_use(): void
    {
        $staff = User::factory()->staff()->create();
        $pricing = Pricing::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'status' => VehicleStatus::Available,
        ]);

        $this->actingAs($staff)
            ->put(route('staff.fleet.update', $vehicle), [
                'status' => VehicleStatus::InUse->value,
                'driver_id' => null,
            ])
            ->assertRedirect(route('staff.fleet.index'));

        $this->assertSame(VehicleStatus::InUse, $vehicle->fresh()->status);
    }

    public function test_staff_cannot_create_or_archive_fleet(): void
    {
        $staff = User::factory()->staff()->create();
        $pricing = Pricing::factory()->create();
        $vehicle = Vehicle::factory()->create(['pricing_id' => $pricing->id]);

        $this->actingAs($staff)
            ->get(route('admin.fleet.index'))
            ->assertRedirect(route('staff.home'));

        $this->actingAs($staff)
            ->get(route('admin.fleet.create'))
            ->assertRedirect(route('staff.home'));

        $this->actingAs($staff)
            ->post(route('admin.fleet.store'), [
                'plate_number' => 'BAD-0001',
                'brand' => 'Test',
                'color' => 'Red',
                'pricing_id' => $pricing->id,
                'status' => VehicleStatus::Available->value,
            ])
            ->assertRedirect(route('staff.home'));

        $this->actingAs($staff)
            ->delete(route('admin.fleet.destroy', $vehicle))
            ->assertRedirect(route('staff.home'));

        $this->assertNull(
            Vehicle::query()->where('plate_number', 'BAD-0001')->first(),
        );
    }

    public function test_assigning_driver_clears_previous_vehicle(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $pricing = Pricing::factory()->create();
        $driver = User::factory()->driver()->create();
        $otherVehicle = Vehicle::factory()->create([
            'plate_number' => 'VAN-OLD',
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
        ]);
        $targetVehicle = Vehicle::factory()->create([
            'plate_number' => 'TRK-NEW',
            'pricing_id' => $pricing->id,
            'driver_id' => null,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.fleet.update', $targetVehicle), [
                'plate_number' => 'TRK-NEW',
                'brand' => $targetVehicle->brand,
                'color' => $targetVehicle->color,
                'pricing_id' => $pricing->id,
                'status' => VehicleStatus::Available->value,
                'driver_id' => $driver->id,
            ])
            ->assertRedirect(route('admin.fleet.index'));

        $targetVehicle->refresh();
        $otherVehicle->refresh();
        $this->assertSame($driver->id, $targetVehicle->driver_id);
        $this->assertNull($otherVehicle->driver_id);
    }

    public function test_customer_cannot_access_staff_fleet(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('staff.fleet.index'))
            ->assertRedirect(route('customer.home'));
    }
}
