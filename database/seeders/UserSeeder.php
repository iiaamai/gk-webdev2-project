<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Carlos Reyes',
                'email' => SeedAccounts::ADMIN_EMAIL,
                'password' => SeedAccounts::ADMIN_PASSWORD,
                'mobile' => '09171234501',
                'role' => UserRole::SystemAdmin,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Ana Mendoza',
                'email' => SeedAccounts::STAFF_EMAIL,
                'password' => SeedAccounts::STAFF_PASSWORD,
                'mobile' => '09171234502',
                'role' => UserRole::Staff,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Maria Santos',
                'email' => SeedAccounts::CUSTOMER_EMAIL,
                'password' => SeedAccounts::PORTAL_PASSWORD,
                'mobile' => '09171234503',
                'role' => UserRole::Customer,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'James Lim',
                'email' => SeedAccounts::CUSTOMER_TWO_EMAIL,
                'password' => SeedAccounts::PORTAL_PASSWORD,
                'mobile' => '09171234504',
                'role' => UserRole::Customer,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Reynaldo Cruz',
                'email' => SeedAccounts::DRIVER_EMAIL,
                'password' => SeedAccounts::PORTAL_PASSWORD,
                'mobile' => '09171234505',
                'role' => UserRole::Driver,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Mark Villanueva',
                'email' => SeedAccounts::DRIVER_TWO_EMAIL,
                'password' => SeedAccounts::PORTAL_PASSWORD,
                'mobile' => '09171234506',
                'role' => UserRole::Driver,
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $attributes) {
            $user = User::query()->withArchived()->updateOrCreate(
                ['email' => $attributes['email']],
                $attributes,
            );

            if ($user->isArchived()) {
                $user->unarchive();
            }
        }
    }
}
