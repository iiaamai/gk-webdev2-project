<?php

namespace App\Http\Requests\Admin;

use App\Models\Pricing;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StorePricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User
            && $this->user()->can('create', Pricing::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'vehicle_type' => ['required', 'string', 'max:255', 'unique:pricings,vehicle_type'],
            'amount' => ['required', 'numeric', 'min:0'],
            'capacity_kg' => ['required', 'integer', 'min:1'],
        ];
    }
}
