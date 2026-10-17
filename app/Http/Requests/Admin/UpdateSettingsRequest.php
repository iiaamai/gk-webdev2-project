<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User
            && $this->user()->can('viewAny', Setting::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'support_email' => ['required', 'string', 'email', 'max:255'],
            'booking_seq' => ['required', 'integer', 'min:1'],
            'map_center_lat' => ['required', 'numeric', 'between:-90,90'],
            'map_center_lng' => ['required', 'numeric', 'between:-180,180'],
            'map_zoom' => ['nullable', 'integer', 'min:1', 'max:22'],
            'dropoff_change_fee' => ['nullable', 'numeric', 'min:0'],
            'qr_ph' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,gif', 'max:20480'],
        ];
    }
}
