<?php

namespace App\Http\Requests\Staff;

use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vehicle = $this->route('vehicle');

        if (! $vehicle instanceof Vehicle || ! $this->user() instanceof User) {
            return false;
        }

        if (! $this->user()->can('update', $vehicle)) {
            return false;
        }

        return $vehicle->status !== VehicleStatus::InUse;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(VehicleStatus::class)],
            'driver_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Driver->value),
            ],
        ];
    }
}
