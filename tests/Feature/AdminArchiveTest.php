<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_archive_pages(): void
    {
        $this->get(route('admin.bookings.archived'))->assertRedirect(route('login'));
        $this->get(route('admin.users.archived'))->assertRedirect(route('login'));
        $this->get(route('admin.fleet.archived'))->assertRedirect(route('login'));
        $this->get(route('admin.pricing.archived'))->assertRedirect(route('login'));
    }

    public function test_staff_cannot_open_admin_archive_pages(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get(route('admin.bookings.archived'))->assertRedirect(route('staff.home'));
        $this->actingAs($staff)->get(route('admin.users.archived'))->assertRedirect(route('staff.home'));
        $this->actingAs($staff)->get(route('admin.fleet.archived'))->assertRedirect(route('staff.home'));
        $this->actingAs($staff)->get(route('admin.pricing.archived'))->assertRedirect(route('staff.home'));
    }

    public function test_admin_live_lists_link_to_archive_pages(): void
    {
        $admin = User::factory()->systemAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSee(route('admin.bookings.archived'), false);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee(route('admin.users.archived'), false);

        $this->actingAs($admin)
            ->get(route('admin.fleet.index'))
            ->assertOk()
            ->assertSee(route('admin.fleet.archived'), false);

        $this->actingAs($admin)
            ->get(route('admin.pricing.index'))
            ->assertOk()
            ->assertSee(route('admin.pricing.archived'), false);
    }

    public function test_admin_can_restore_archived_booking(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $booking = Booking::factory()->create();
        $booking->archive();

        $this->actingAs($admin)
            ->get(route('admin.bookings.archived'))
            ->assertOk()
            ->assertSee($booking->booking_number);

        $this->actingAs($admin)
            ->post(route('admin.bookings.restore', $booking))
            ->assertRedirect(route('admin.bookings.archived'));

        $this->assertFalse($booking->fresh()->isArchived());
    }

    public function test_admin_can_permanently_delete_archived_booking_without_related_records(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $booking = Booking::factory()->create();
        $booking->archive();

        $this->actingAs($admin)
            ->delete(route('admin.bookings.force-destroy', $booking))
            ->assertRedirect(route('admin.bookings.archived'));

        $this->assertNull(Booking::withArchived()->find($booking->id));
    }

    public function test_admin_cannot_permanently_delete_archived_booking_with_invoice(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $booking = Booking::factory()->create();
        Invoice::factory()->create(['booking_id' => $booking->id]);
        $booking->archive();

        $this->actingAs($admin)
            ->delete(route('admin.bookings.force-destroy', $booking))
            ->assertRedirect(route('admin.bookings.archived'))
            ->assertSessionHasErrors('archive');

        $this->assertNotNull(Booking::withArchived()->find($booking->id));
    }

    public function test_admin_can_restore_and_permanently_delete_archived_user_without_related_records(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $staff = User::factory()->staff()->create(['email' => 'archived-staff@gk.test']);
        $staff->archive();

        $this->actingAs($admin)
            ->post(route('admin.users.restore', $staff))
            ->assertRedirect(route('admin.users.archived'));

        $this->assertFalse($staff->fresh()->isArchived());

        $staff->refresh()->archive();

        $this->actingAs($admin)
            ->delete(route('admin.users.force-destroy', $staff))
            ->assertRedirect(route('admin.users.archived'));

        $this->assertNull(User::withArchived()->find($staff->id));
    }

    public function test_admin_cannot_permanently_delete_user_referenced_by_bookings(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $customer = User::factory()->customer()->create();
        Booking::factory()->create(['customer_id' => $customer->id]);
        $customer->archive();

        $this->actingAs($admin)
            ->delete(route('admin.users.force-destroy', $customer))
            ->assertRedirect(route('admin.users.archived'))
            ->assertSessionHasErrors('archive');

        $this->assertNotNull(User::withArchived()->find($customer->id));
    }

    public function test_admin_can_restore_and_permanently_delete_archived_vehicle_without_bookings(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $vehicle = Vehicle::factory()->create();
        $vehicle->archive();

        $this->actingAs($admin)
            ->post(route('admin.fleet.restore', $vehicle))
            ->assertRedirect(route('admin.fleet.archived'));

        $this->assertFalse($vehicle->fresh()->isArchived());

        $vehicle->refresh()->archive();

        $this->actingAs($admin)
            ->delete(route('admin.fleet.force-destroy', $vehicle))
            ->assertRedirect(route('admin.fleet.archived'));

        $this->assertNull(Vehicle::withArchived()->find($vehicle->id));
    }

    public function test_admin_cannot_permanently_delete_vehicle_referenced_by_bookings(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $vehicle = Vehicle::factory()->create();
        Booking::factory()->create(['vehicle_id' => $vehicle->id]);
        $vehicle->archive();

        $this->actingAs($admin)
            ->delete(route('admin.fleet.force-destroy', $vehicle))
            ->assertRedirect(route('admin.fleet.archived'))
            ->assertSessionHasErrors('archive');

        $this->assertNotNull(Vehicle::withArchived()->find($vehicle->id));
    }

    public function test_admin_can_restore_and_permanently_delete_unused_archived_pricing(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $pricing = Pricing::factory()->create([
            'vehicle_type' => 'Archive-only truck',
            'amount' => 1000,
            'capacity_kg' => 500,
        ]);
        $pricing->archive();

        $this->actingAs($admin)
            ->post(route('admin.pricing.restore', $pricing))
            ->assertRedirect(route('admin.pricing.archived'));

        $this->assertFalse($pricing->fresh()->isArchived());

        $pricing->refresh()->archive();

        $this->actingAs($admin)
            ->delete(route('admin.pricing.force-destroy', $pricing))
            ->assertRedirect(route('admin.pricing.archived'));

        $this->assertNull(Pricing::withArchived()->find($pricing->id));
    }

    public function test_admin_cannot_permanently_delete_pricing_referenced_by_vehicles(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $pricing = Pricing::factory()->create([
            'vehicle_type' => 'Linked truck',
            'amount' => 2000,
            'capacity_kg' => 800,
        ]);
        Vehicle::factory()->create(['pricing_id' => $pricing->id]);
        $pricing->archive();

        $this->actingAs($admin)
            ->delete(route('admin.pricing.force-destroy', $pricing))
            ->assertRedirect(route('admin.pricing.archived'))
            ->assertSessionHasErrors('archive');

        $this->assertNotNull(Pricing::withArchived()->find($pricing->id));
    }

    public function test_restore_of_live_record_returns_not_found(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $booking = Booking::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.bookings.restore', $booking))
            ->assertNotFound();
    }
}
