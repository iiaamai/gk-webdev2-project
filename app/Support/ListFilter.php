<?php

namespace App\Support;

use Illuminate\Http\Request;

class ListFilter
{
    public const int PER_PAGE = 25;

    /**
     * @param  list<string>  $keys
     */
    public static function isActive(Request $request, array $keys): bool
    {
        foreach ($keys as $key) {
            if ($request->filled($key)) {
                return true;
            }
        }

        return false;
    }

    public static function searchTerm(Request $request): string
    {
        return trim((string) $request->query('q', ''));
    }
}
