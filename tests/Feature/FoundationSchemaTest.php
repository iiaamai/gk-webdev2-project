<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Pricing;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FoundationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_tables_exist(): void
    {
        foreach ([
            'users',
            'vehicles',
            'pricings',
            'bookings',
            'eirs',
            'pods',
            'invoices',
            'ratings',
            'activity_logs',
            'settings',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_users_table_has_role_and_archive_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'role',
            'mobile',
            'avatar_path',
            'archived_at',
        ]));
        $this->assertFalse(Schema::hasColumn('users', 'vehicle_type'));
        $this->assertFalse(Schema::hasColumn('users', 'plate'));
        $this->assertFalse(Schema::hasColumn('users', 'capacity_kg'));
    }

    public function test_vehicles_table_has_driver_assignment_column(): void
    {
        $this->assertTrue(Schema::hasColumn('vehicles', 'driver_id'));
        $this->assertTrue(Schema::hasColumn('vehicles', 'pricing_id'));
        $this->assertFalse(Schema::hasColumn('vehicles', 'type'));
        $this->assertTrue(Schema::hasColumn('bookings', 'pricing_id'));
        $this->assertFalse(Schema::hasColumn('bookings', 'vehicle_type'));
        $this->assertFalse(Schema::hasColumn('bookings', 'payout'));
    }

    public function test_database_seeder_creates_demo_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, Pricing::query()->count());
        $this->assertSame(5, Vehicle::query()->count());
        $this->assertSame(5, User::query()->count());
        $this->assertSame(6, Booking::query()->count());
        $this->assertSame('GK Trucking Services', Setting::getValue('company_name'));

        $driver = User::query()->where('email', 'driver@gk.test')->first();
        $this->assertNotNull($driver);
        $this->assertSame(UserRole::Driver, $driver->role);
        $this->assertNotNull($driver->email_verified_at);
        $this->assertNotNull($driver->assignedVehicle);
        $this->assertSame('ABC-1234', $driver->assignedVehicle->plate_number);

        $this->assertSame(
            $driver->id,
            Vehicle::query()->where('plate_number', 'ABC-1234')->value('driver_id'),
        );

        $this->assertTrue(
            Booking::query()->where('status', BookingStatus::Pending)->whereNull('gatepass_path')->exists()
        );
        $this->assertTrue(
            Booking::query()->where('status', BookingStatus::Pending)->whereNotNull('gatepass_path')->exists()
        );
        $this->assertTrue(Booking::query()->where('status', BookingStatus::Completed)->exists());
    }

    public function test_archivable_hides_archived_rows_by_default(): void
    {
        $vehicle = Vehicle::factory()->create();
        $vehicle->archive();

        $this->assertNull(Vehicle::query()->find($vehicle->id));
        $this->assertNotNull(Vehicle::withArchived()->find($vehicle->id));
        $this->assertTrue(Vehicle::onlyArchived()->whereKey($vehicle->id)->exists());
    }
}
