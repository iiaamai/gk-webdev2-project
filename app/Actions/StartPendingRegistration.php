<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Mail\CompleteRegistrationMail;
use App\Models\PendingRegistration;
use App\Models\User;
use App\Support\MailIntegration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class StartPendingRegistration
{
    /**
     * @param  array{name: string, email: string, mobile?: string|null, password: string}  $data
     */
    public function execute(array $data, UserRole $role): ?User
    {
        if (! MailIntegration::isEnabled()) {
            return User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'] ?? null,
                'password' => $data['password'],
                'role' => $role,
                'email_verified_at' => now(),
            ]);
        }

        $pending = PendingRegistration::query()->updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'mobile' => $data['mobile'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => $role,
                'expires_at' => now()->addDay(),
            ],
        );

        Mail::to($pending->email)->send(new CompleteRegistrationMail($pending));

        return null;
    }
}
