<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class IpLocationResolver
{
    public function resolve(?string $ip): ?string
    {
        if (! config('gk.activity_ip_lookup')) {
            return null;
        }

        if ($ip === null || $ip === '' || $ip === '127.0.0.1' || $ip === '::1') {
            return null;
        }

        try {
            $response = Http::timeout(3)
                ->get('http://ip-api.com/json/'.$ip, [
                    'fields' => 'status,city,regionName,country',
                ]);

            if (! $response->ok() || $response->json('status') !== 'success') {
                return null;
            }

            $parts = array_filter([
                $response->json('city'),
                $response->json('regionName'),
                $response->json('country'),
            ]);

            return $parts === [] ? null : implode(', ', $parts);
        } catch (Throwable) {
            return null;
        }
    }
}
