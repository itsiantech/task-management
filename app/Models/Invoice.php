<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'client_id',
        'task_id',
        'status',
        'invoice_date',
        'due_date',
        'date_paid',
        'cancelled_date',
        'refunded_date',
        'payment_method',
        'sub_total',
        'advance_paid_credit',
        'total_amount',
        'total_due',
        'notes',
        'published_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'date_paid' => 'date',
        'cancelled_date' => 'date',
        'refunded_date' => 'date',
        'published_at' => 'datetime',
        'sub_total' => 'decimal:2',
        'advance_paid_credit' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_due' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = (int) (static::query()->max('invoice_number') ?? 0) + 1;
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InvoiceTransaction::class)->orderBy('transaction_date', 'asc');
    }

    public function getInvoiceNumberLabelAttribute(): string
    {
        return '#'.$this->invoice_number;
    }

    public function recalculateTotals(): void
    {
        $subTotal = (float) $this->items()->sum('amount');
        $advancePaidCredit = (float) ($this->advance_paid_credit ?? 0);
        $paymentTotal = (float) $this->transactions()->sum('amount');

        $this->sub_total = $subTotal;
        $this->total_amount = max(0, $subTotal - $advancePaidCredit);
        $this->total_due = max(0, $this->total_amount - $paymentTotal);

        $this->saveQuietly();
    }

    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            'cash_in' => 'Cash',
            'bkash' => 'bKash',
            'nagad' => 'Nagad',
            'bank' => 'Bank',
            default => ucfirst((string) $this->payment_method),
        };
    }
}
