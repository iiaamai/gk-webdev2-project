<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\Archivable;
use App\Support\MailIntegration;
use Database\Factories\UserFactory;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'mobile',
    'avatar_path',
    'role',
    'email_verified_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Archivable, HasFactory, Notifiable;

    public function hasAvatar(): bool
    {
        return filled($this->avatar_path);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'archived_at' => 'datetime',
        ];
    }

    /**
     * Skip sending while Mail integration is a placeholder.
     */
    public function sendEmailVerificationNotification(): void
    {
        if (! MailIntegration::isEnabled()) {
            return;
        }

        $this->notify(new VerifyEmail);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function customerBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function driverBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'driver_id');
    }

    /**
     * @return HasOne<Vehicle, $this>
     */
    public function assignedVehicle(): HasOne
    {
        return $this->hasOne(Vehicle::class, 'driver_id');
    }

    /**
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    public function isDriver(): bool
    {
        return $this->role === UserRole::Driver;
    }

    public function isStaff(): bool
    {
        return $this->role === UserRole::Staff;
    }

    public function isSystemAdmin(): bool
    {
        return $this->role === UserRole::SystemAdmin;
    }
}
