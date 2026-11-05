<?php

namespace App\Http\Requests\Admin;

use App\Models\Pricing;
use App\Models\User;
use App\Support\MetricWeight;
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
            'capacity_tons' => ['required_without:capacity_kg', 'nullable', 'numeric', 'min:0.001', 'max:999.999'],
            'capacity_kg' => ['required_without:capacity_tons', 'nullable', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('capacity_tons')) {
            $this->merge([
                'capacity_kg' => MetricWeight::tonsToKg((float) $this->input('capacity_tons')),
            ]);
        }
    }
}
