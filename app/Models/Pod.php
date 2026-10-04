<?php

namespace App\Models;

use App\Models\Concerns\Archivable;
use Database\Factories\PodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'booking_id',
    'photo_paths',
    'captured_at',
])]
class Pod extends Model
{
    /** @use HasFactory<PodFactory> */
    use Archivable, HasFactory;

    public $incrementing = false;

    protected $primaryKey = 'booking_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'photo_paths' => 'array',
            'captured_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
