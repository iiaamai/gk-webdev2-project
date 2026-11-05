<?php

namespace App\Http\Requests\Admin;

use App\Models\Pricing;
use App\Models\User;
use App\Support\MetricWeight;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pricing = $this->route('pricing');

        return $pricing instanceof Pricing
            && $this->user() instanceof User
            && $this->user()->can('update', $pricing);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $pricing = $this->route('pricing');

        return [
            'vehicle_type' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pricings', 'vehicle_type')->ignore($pricing),
            ],
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
