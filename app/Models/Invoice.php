<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentProofStatus;
use App\Models\Concerns\Archivable;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'booking_id',
    'amount',
    'status',
    'issued_at',
    'paid_at',
    'notes',
    'paymongo_reference',
    'payment_proof_path',
    'payment_proof_status',
    'payment_proof_submitted_at',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use Archivable, HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'unpaid',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => InvoiceStatus::class,
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
            'payment_proof_status' => PaymentProofStatus::class,
            'payment_proof_submitted_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function hasPaymentProof(): bool
    {
        return filled($this->payment_proof_path);
    }

    public function hasPendingPaymentProof(): bool
    {
        return $this->payment_proof_status === PaymentProofStatus::Pending
            && $this->hasPaymentProof();
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopePendingPaymentProof(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::Unpaid)
            ->where('payment_proof_status', PaymentProofStatus::Pending);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
