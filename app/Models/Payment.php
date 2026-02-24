<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Traits\Auditable;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use Auditable, HasUlid;

    protected static function booted(): void
    {
        static::saved(function (Payment $payment) {
            $payment->recalculateInvoice();
        });

        static::deleted(function (Payment $payment) {
            $payment->recalculateInvoice();
        });
    }

    /**
     * Recalculate the parent invoice's paid amount and balance.
     */
    protected function recalculateInvoice(): void
    {
        $invoice = $this->invoice;
        if (! $invoice) {
            return;
        }

        $totalPaid = (int) $invoice->payments()->sum('amount');
        $balance = (int) $invoice->total - $totalPaid;

        $invoice->forceFill([
            'paid' => $totalPaid,
            'balance' => $balance,
        ])->save();
    }

    protected $fillable = [
        'invoice_id',
        'amount',
        'method',
        'reference_number',
        'received_by',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'method' => PaymentMethod::class,
            'paid_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
