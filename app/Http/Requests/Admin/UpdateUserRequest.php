<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Vehicle;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User
            && $this->user() instanceof User
            && $this->user()->can('update', $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'mobile' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'vehicle_id' => [
                'nullable',
                'integer',
                'prohibited_unless:role,driver',
                Rule::exists('vehicles', 'id'),
                $this->vehicleMustBeUnassigned($user instanceof User ? $user : null),
            ],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    protected function vehicleMustBeUnassigned(?User $currentUser = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($currentUser): void {
            if ($value === null || $value === '') {
                return;
            }

            $vehicle = Vehicle::query()->find($value);

            if ($vehicle === null) {
                return;
            }

            if ($vehicle->driver_id === null) {
                return;
            }

            if ($currentUser instanceof User && (int) $vehicle->driver_id === (int) $currentUser->id) {
                return;
            }

            $fail('The selected fleet vehicle is already assigned to another driver.');
        };
    }
}
