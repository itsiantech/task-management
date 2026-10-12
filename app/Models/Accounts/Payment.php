<?php

declare(strict_types=1);

namespace App\Models\Accounts;

use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'account_payments';

    protected static function newFactory()
    {
        return \Database\Factories\PaymentFactory::new();
    }

    protected $fillable = [
        'invoice_id',
        'client_id',
        'order_id',
        'amount',
        'payment_date',
        'transaction_date',
        'gateway',
        'method',
        'receiving_account',
        'transaction_id',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public const AUDITED_FIELDS = [
        'invoice_id',
        'client_id',
        'order_id',
        'amount',
        'payment_date',
        'gateway',
        'method',
        'receiving_account',
        'transaction_id',
        'note',
    ];

    /**
     * Transient audit context for the next save. Plain properties (not
     * attributes) so they are never written to the database.
     */
    public ?string $editReason = null;

    public mixed $editActor = null;

    protected static function booted(): void
    {
        static::creating(function (self $payment) {
            if (empty($payment->created_by)) {
                $payment->created_by = auth()->id();
            }
            if (empty($payment->transaction_date)) {
                $payment->transaction_date = $payment->payment_date ?? now()->toDateString();
            }
            if (empty($payment->gateway)) {
                $payment->gateway = $payment->method;
            }
        });

        static::updating(function (self $payment) {
            if (empty($payment->updated_by)) {
                $payment->updated_by = auth()->id();
            }
        });

        static::updated(function (self $payment) {
            $changes = array_intersect_key($payment->getChanges(), array_flip(self::AUDITED_FIELDS));
            if ($changes !== []) {
                $old = array_intersect_key($payment->getOriginal(), array_flip(self::AUDITED_FIELDS));
                PaymentEdit::create([
                    'payment_id' => $payment->id,
                    'edited_by' => $payment->editActor ?? $payment->getAttribute('edit_actor') ?? auth()->id() ?? $payment->created_by,
                    'edited_at' => now(),
                    'old_values' => $old,
                    'new_values' => array_intersect_key($payment->getAttributes(), array_flip(self::AUDITED_FIELDS)),
                    'reason' => $payment->editReason ?? $payment->getAttribute('edit_reason') ?? 'Manual edit',
                ]);
            }

            if ($payment->invoice_id) {
                $payment->invoice?->recalculateTotals();
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'order_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function edits(): HasMany
    {
        return $this->hasMany(PaymentEdit::class);
    }

    public function getMethodEnumAttribute(): PaymentMethod
    {
        return PaymentMethod::from($this->method);
    }

    public function getFormattedAmountAttribute(): string
    {
        return '৳' . number_format((float) $this->amount, 2);
    }

    public function hasEdits(): bool
    {
        return $this->edits()->count() > 0;
    }
}
