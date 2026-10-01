<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User
            && $this->user()->can('create', Vehicle::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plate_number' => ['required', 'string', 'max:50', 'unique:vehicles,plate_number'],
            'brand' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:100'],
            'pricing_id' => ['required', 'integer', Rule::exists('pricings', 'id')],
            'status' => ['required', Rule::enum(VehicleStatus::class)],
            'driver_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Driver->value),
            ],
        ];
    }
}
