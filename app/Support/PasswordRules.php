<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

final class PasswordRules
{
    public static function required(): array
    {
        return ['required', 'string', 'confirmed', self::rule()];
    }

    public static function rule(): Password
    {
        return Password::min(8)
            ->mixedCase()
            ->symbols();
    }
}
