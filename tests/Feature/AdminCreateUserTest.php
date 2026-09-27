<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCreateUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_can_view_user_profile(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $driver = User::factory()->driver()->create();
        $pricing = Pricing::factory()->create(['vehicle_type' => 'Wing van', 'amount' => 5000, 'capacity_kg' => 2000]);
        Vehicle::factory()->create([
            'plate_number' => 'ABC-1234',
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $driver))
            ->assertOk()
            ->assertSee('Basic information')
            ->assertSee('Fleet vehicle')
            ->assertSee('ABC-1234');
    }

    public function test_system_admin_can_create_staff_user(): void
    {
        $admin = User::factory()->systemAdmin()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Staff',
            'email' => 'new.staff@example.com',
            'mobile' => '09170001111',
            'role' => UserRole::Staff->value,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $staff = User::query()->where('email', 'new.staff@example.com')->first();
        $this->assertNotNull($staff);
        $this->assertSame(UserRole::Staff, $staff->role);
        $this->assertNotNull($staff->email_verified_at);
    }

    public function test_system_admin_can_create_driver_without_vehicle_fields(): void
    {
        $admin = User::factory()->systemAdmin()->create();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Driver',
            'email' => 'new.driver.admin@example.com',
            'role' => UserRole::Driver->value,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('admin.users.index'));

        $driver = User::query()->where('email', 'new.driver.admin@example.com')->first();
        $this->assertNotNull($driver);
        $this->assertSame(UserRole::Driver, $driver->role);
        $this->assertNull($driver->assignedVehicle);
    }

    public function test_system_admin_can_create_driver_with_fleet_vehicle(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $pricing = Pricing::factory()->create(['vehicle_type' => 'L300 van', 'amount' => 4500, 'capacity_kg' => 1000]);
        $vehicle = Vehicle::factory()->create([
            'plate_number' => 'VAN-1001',
            'pricing_id' => $pricing->id,
        ]);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Fleet Driver',
            'email' => 'fleet.driver@example.com',
            'role' => UserRole::Driver->value,
            'vehicle_id' => $vehicle->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('admin.users.index'));

        $driver = User::query()->where('email', 'fleet.driver@example.com')->first();
        $this->assertNotNull($driver);
        $this->assertSame($driver->id, $vehicle->fresh()->driver_id);
        $this->assertSame('VAN-1001', $driver->assignedVehicle?->plate_number);
        $this->assertSame('L300 van', $driver->assignedVehicle?->pricing?->vehicle_type);
        $this->assertSame(1000, $driver->assignedVehicle?->pricing?->capacity_kg);
    }

    public function test_non_admin_cannot_create_users(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->post(route('admin.users.store'), [
                'name' => 'Nope',
                'email' => 'nope@example.com',
                'role' => UserRole::Staff->value,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('staff.home'));

        $this->assertDatabaseMissing('users', ['email' => 'nope@example.com']);
    }

    public function test_guest_cannot_create_users(): void
    {
        $this->post(route('admin.users.store'), [
            'name' => 'Nope',
            'email' => 'nope@example.com',
            'role' => UserRole::Staff->value,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('login'));
    }
}
