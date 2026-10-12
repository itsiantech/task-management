<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Accounts\Payment as AccountPayment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_number',
        'client_id',
        'billing_company',
        'billing_name',
        'billing_address',
        'billing_phone',
        'billing_email',
        'billing_country',
        'task_id',
        'status',
        'invoice_date',
        'due_date',
        'date_paid',
        'cancelled_date',
        'refunded_date',
        'sub_total',
        'advance_paid_credit',
        'total_amount',
        'total_due',
        'notes',
        'payment_method',
        'published_at',
        'void_reason',
        'held_at',
        'held_by',
        'voided_at',
        'voided_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'date_paid' => 'date',
        'cancelled_date' => 'date',
        'refunded_date' => 'date',
        'published_at' => 'datetime',
        'held_at' => 'datetime',
        'voided_at' => 'datetime',
        'sub_total' => 'decimal:2',
        'advance_paid_credit' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_due' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
        });

        static::deleting(function (self $invoice) {
            throw new \Exception('Invoices cannot be deleted. Use void instead.');
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "INV-{$year}-";

        $lastNumber = \DB::transaction(function () use ($prefix) {
            return \DB::table('invoices')
                ->lockForUpdate()
                ->where('invoice_number', 'like', $prefix . '%')
                ->max('invoice_number');
        });

        if ($lastNumber) {
            $lastSequence = (int) str_replace($prefix, '', $lastNumber);
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad((string) $newSequence, 4, '0', STR_PAD_LEFT);
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

    public function payments(): HasMany
    {
        return $this->hasMany(AccountPayment::class)->orderBy('payment_date', 'asc');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountPayment::class)->orderBy('payment_date', 'asc');
    }

    public function heldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'held_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function getInvoiceNumberLabelAttribute(): string
    {
        return (string) $this->invoice_number;
    }

    public function getStatusEnumAttribute(): ?InvoiceStatus
    {
        return InvoiceStatus::tryFrom((string) $this->status);
    }

    public function paymentMethodLabel(): string
    {
        $raw = strtolower((string) ($this->payment_method ?? ''));
        if ($raw === 'cash_in') {
            $raw = PaymentMethod::CASH->value;
        }
        $method = PaymentMethod::tryFrom($raw);

        return $method?->label() ?? ucfirst((string) ($this->payment_method ?? '—'));
    }

    public function recalculateTotals(): void
    {
        $subTotal = (float) $this->items()->sum('line_total');
        $advancePaidCredit = (float) ($this->advance_paid_credit ?? 0);
        $paymentTotal = (float) $this->payments()->sum('amount');

        $this->sub_total = $subTotal;
        $this->total_amount = max(0, $subTotal - $advancePaidCredit);
        $this->total_due = max(0, $this->total_amount - $paymentTotal);

        $this->updateStatusBasedOnPayments();

        $this->saveQuietly();
    }

    public function updateStatusBasedOnPayments(): void
    {
        $paymentTotal = (float) $this->payments()->sum('amount');
        $totalAmount = (float) $this->total_amount;

        if ($this->status === InvoiceStatus::VOID->value) {
            return;
        }

        if ($this->status === InvoiceStatus::HOLD->value) {
            return;
        }

        // Drafts keep their status until published.
        if ($this->status === InvoiceStatus::DRAFT->value) {
            return;
        }

        if ($paymentTotal >= $totalAmount && $totalAmount > 0) {
            $this->status = InvoiceStatus::PAID->value;
            $this->date_paid = now();
        } elseif ($paymentTotal > 0) {
            $this->status = InvoiceStatus::PARTIAL->value;
        } elseif ($this->due_date && $this->due_date->isPast()) {
            $this->status = InvoiceStatus::DUE->value;
        } else {
            $this->status = InvoiceStatus::UNPAID->value;
        }
    }

    public function isOverdue(): bool
    {
        return (bool) ($this->due_date && $this->due_date->isPast()
            && ! in_array($this->status, [InvoiceStatus::PAID->value, InvoiceStatus::HOLD->value, InvoiceStatus::VOID->value], true));
    }

    public function getFormattedTotalAttribute(): string
    {
        return '৳' . number_format((float) $this->total_amount, 2);
    }

    public function getFormattedDueAttribute(): string
    {
        return '৳' . number_format((float) $this->total_due, 2);
    }
}
