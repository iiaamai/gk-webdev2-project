<?php

namespace App\Models;

use Database\Factories\DriverAttendanceLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'driver_id',
    'timed_in_at',
    'timed_in_by_id',
    'timed_out_at',
    'timed_out_by_id',
])]
class DriverAttendanceLog extends Model
{
    /** @use HasFactory<DriverAttendanceLogFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'timed_in_at' => 'datetime',
            'timed_out_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function timedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'timed_in_by_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function timedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'timed_out_by_id');
    }

    public function isOpen(): bool
    {
        return $this->timed_out_at === null;
    }
}
