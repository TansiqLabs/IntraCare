<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Traits\Auditable;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use Auditable, HasUlid, SoftDeletes;

    protected static function booted(): void
    {
        static::forceDeleting(function (Payment $model) {
            throw new \RuntimeException('Force-deleting payment records is prohibited.');
        });

        static::saved(function (Payment $payment) {
            $payment->recalculateInvoice();
        });

        static::deleted(function (Payment $payment) {
            $payment->recalculateInvoice();
        });
    }

    /**
     * Recalculate the parent invoice's paid amount, balance, and status.
     */
    protected function recalculateInvoice(): void
    {
        $invoice = $this->invoice()->withTrashed()->first();
        if (! $invoice) {
            return;
        }

        $totalPaid = (int) $invoice->payments()->sum('amount');
        $balance = (int) $invoice->total - $totalPaid;

        // Auto-transition invoice status based on payment state,
        // but never override Cancelled or Refunded statuses.
        $status = $invoice->status;
        if (! in_array($status, [InvoiceStatus::Cancelled, InvoiceStatus::Refunded], true)) {
            $status = match (true) {
                $balance <= 0 && $totalPaid > 0 => InvoiceStatus::Paid,
                $totalPaid > 0 => InvoiceStatus::Partial,
                default => InvoiceStatus::Issued,
            };
        }

        $invoice->forceFill([
            'paid' => $totalPaid,
            'balance' => $balance,
            'status' => $status,
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
