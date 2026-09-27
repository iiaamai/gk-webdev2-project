<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>|null  $properties
     */
    public function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        ?array $properties = null,
        ?User $user = null,
        ?Request $request = null,
    ): ActivityLog {
        $request ??= request();
        $actor = $user ?? Auth::user();

        return ActivityLog::query()->create([
            'user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject !== null ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'ip_address' => $request instanceof Request ? $request->ip() : null,
            'ip_location' => null,
            'geo_lat' => $this->optionalFloat($request, 'geo_lat'),
            'geo_lng' => $this->optionalFloat($request, 'geo_lng'),
            'properties' => $properties,
            'created_at' => now('Asia/Manila'),
        ]);
    }

    private function optionalFloat(?Request $request, string $key): ?float
    {
        if (! $request instanceof Request || ! $request->filled($key)) {
            return null;
        }

        return (float) $request->input($key);
    }
}
