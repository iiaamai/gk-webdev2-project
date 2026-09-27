<?php

namespace App\Http\Requests\Admin;

use App\Models\Pricing;
use App\Models\User;
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
            'capacity_kg' => ['required', 'integer', 'min:1'],
        ];
    }
}
