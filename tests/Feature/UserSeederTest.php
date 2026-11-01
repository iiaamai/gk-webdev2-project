<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_seeder_can_run_twice_without_duplicate_emails(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(UserSeeder::class);

        $this->assertSame(5, User::query()->withArchived()->count());
    }

    public function test_user_seeder_restores_archived_demo_driver(): void
    {
        $this->seed(UserSeeder::class);

        $driver = User::query()->withArchived()->where('email', 'driver@gk.test')->firstOrFail();
        $driver->archive();

        $this->seed(UserSeeder::class);

        $driver->refresh();
        $this->assertFalse($driver->isArchived());
    }

    public function test_database_seeder_can_run_twice(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'driver@gk.test']);
    }
}
