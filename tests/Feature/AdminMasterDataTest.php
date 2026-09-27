<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Models\Pricing;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_settings(): void
    {
        $admin = User::factory()->systemAdmin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'company_name' => 'GK Trucking Services',
                'support_email' => 'help@gk.test',
                'booking_seq' => 10,
                'map_center_lat' => 14.6,
                'map_center_lng' => 120.98,
                'map_zoom' => 12,
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('help@gk.test', Setting::getValue('support_email'));
        $this->assertSame('10', Setting::getValue('booking_seq'));
    }

    public function test_admin_can_crud_pricing(): void
    {
        $admin = User::factory()->systemAdmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.pricing.store'), [
                'vehicle_type' => 'Test truck',
                'amount' => 12345.67,
                'capacity_kg' => 2500,
            ])
            ->assertRedirect(route('admin.pricing.index'));

        $pricing = Pricing::query()->where('vehicle_type', 'Test truck')->first();
        $this->assertNotNull($pricing);

        $this->actingAs($admin)
            ->put(route('admin.pricing.update', $pricing), [
                'vehicle_type' => 'Test truck updated',
                'amount' => 15000,
                'capacity_kg' => 2800,
            ])
            ->assertRedirect(route('admin.pricing.index'));

        $pricing->refresh();
        $this->assertSame('Test truck updated', $pricing->vehicle_type);
        $this->assertSame(2800, $pricing->capacity_kg);

        $this->actingAs($admin)
            ->delete(route('admin.pricing.destroy', $pricing))
            ->assertRedirect(route('admin.pricing.index'));

        $this->assertNull(Pricing::query()->find($pricing->id));
        $this->assertNotNull(Pricing::withArchived()->find($pricing->id));
    }

    public function test_admin_can_assign_driver_on_fleet_vehicle(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $driver = User::factory()->driver()->create();
        $otherDriver = User::factory()->driver()->create();
        $pricing = Pricing::factory()->create([
            'vehicle_type' => '4-wheeler truck',
            'amount' => 9200,
            'capacity_kg' => 3000,
        ]);
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'driver_id' => $otherDriver->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.fleet.store'), [
                'plate_number' => 'ASN-0001',
                'brand' => 'Isuzu',
                'color' => 'White',
                'pricing_id' => $pricing->id,
                'status' => VehicleStatus::Available->value,
                'driver_id' => $driver->id,
            ])
            ->assertRedirect(route('admin.fleet.index'));

        $vehicle = Vehicle::query()->where('plate_number', 'ASN-0001')->first();
        $this->assertNotNull($vehicle);
        $this->assertSame($driver->id, $vehicle->driver_id);

        $driver->load('assignedVehicle.pricing');
        $this->assertSame('ASN-0001', $driver->assignedVehicle?->plate_number);
        $this->assertSame('4-wheeler truck', $driver->assignedVehicle?->pricing?->vehicle_type);
        $this->assertSame($pricing->capacity_kg, $driver->assignedVehicle?->pricing?->capacity_kg);

        $this->actingAs($admin)
            ->get(route('admin.fleet.index'))
            ->assertOk()
            ->assertSee('Assigned driver')
            ->assertSee($driver->name);

        $this->actingAs($admin)
            ->get(route('admin.fleet.create'))
            ->assertOk()
            ->assertDontSee($otherDriver->email)
            ->assertDontSee($driver->email);
    }

    public function test_admin_can_crud_fleet(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $pricing = Pricing::factory()->create(['vehicle_type' => 'L300 van', 'amount' => 4500]);

        $this->actingAs($admin)
            ->post(route('admin.fleet.store'), [
                'plate_number' => 'TST-0001',
                'brand' => 'Toyota',
                'color' => 'Silver',
                'pricing_id' => $pricing->id,
                'status' => VehicleStatus::Available->value,
            ])
            ->assertRedirect(route('admin.fleet.index'));

        $vehicle = Vehicle::query()->where('plate_number', 'TST-0001')->first();
        $this->assertNotNull($vehicle);

        $this->actingAs($admin)
            ->put(route('admin.fleet.update', $vehicle), [
                'plate_number' => 'TST-0001',
                'brand' => 'Toyota',
                'color' => 'Red',
                'pricing_id' => $pricing->id,
                'status' => VehicleStatus::Maintenance->value,
            ])
            ->assertRedirect(route('admin.fleet.index'));

        $vehicle->refresh();
        $this->assertSame(VehicleStatus::Maintenance, $vehicle->status);
        $this->assertSame('Toyota', $vehicle->brand);
        $this->assertSame('Red', $vehicle->color);

        $this->actingAs($admin)
            ->delete(route('admin.fleet.destroy', $vehicle))
            ->assertRedirect(route('admin.fleet.index'));

        $this->assertNull(Vehicle::query()->find($vehicle->id));
    }

    public function test_admin_can_archive_user_but_not_self(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $staff = User::factory()->staff()->create(['email' => 'archive-me@gk.test']);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $staff))
            ->assertRedirect(route('admin.users.index'));

        $this->assertNull(User::query()->where('email', 'archive-me@gk.test')->first());

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertForbidden();
    }

    public function test_staff_cannot_access_admin_master_data(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get(route('admin.pricing.index'))->assertRedirect(route('staff.home'));
        $this->actingAs($staff)->get(route('admin.fleet.index'))->assertRedirect(route('staff.home'));
        $this->actingAs($staff)->get(route('admin.settings.edit'))->assertRedirect(route('staff.home'));
        $this->actingAs($staff)->get(route('admin.users.index'))->assertRedirect(route('staff.home'));
    }

    public function test_seeded_demo_passwords_work_after_fresh_seed(): void
    {
        $this->seed();

        $this->post(route('login'), [
            'email' => 'customer@gk.test',
            'password' => 'demo123',
        ])->assertRedirect(route('customer.home'));

        $this->post(route('logout'));
    }
}
