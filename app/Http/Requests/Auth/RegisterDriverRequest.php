<?php

namespace App\Http\Requests\Auth;

use App\Support\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;

class RegisterDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'password' => PasswordRules::required(),
        ];
    }
}
