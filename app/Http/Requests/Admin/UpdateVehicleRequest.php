<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vehicle = $this->route('vehicle');

        return $vehicle instanceof Vehicle
            && $this->user() instanceof User
            && $this->user()->can('update', $vehicle);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $vehicle = $this->route('vehicle');

        return [
            'plate_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vehicles', 'plate_number')->ignore($vehicle),
            ],
            'brand' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:100'],
            'pricing_id' => ['required', 'integer', Rule::exists('pricings', 'id')],
            'status' => ['required', Rule::enum(VehicleStatus::class)],
            'driver_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Driver->value),
                $this->driverMustBeUnassigned($vehicle instanceof Vehicle ? $vehicle : null),
            ],
        ];
    }

    protected function driverMustBeUnassigned(?Vehicle $currentVehicle = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($currentVehicle): void {
            if ($value === null || $value === '') {
                return;
            }

            $assignedElsewhere = Vehicle::query()
                ->where('driver_id', $value)
                ->when(
                    $currentVehicle instanceof Vehicle,
                    fn ($query) => $query->whereKeyNot($currentVehicle->id),
                )
                ->exists();

            if ($assignedElsewhere) {
                $fail('The selected driver is already assigned to another fleet vehicle.');
            }
        };
    }
}
