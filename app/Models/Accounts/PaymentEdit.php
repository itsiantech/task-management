<?php

declare(strict_types=1);

namespace App\Models\Accounts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEdit extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\PaymentEditFactory::new();
    }

    protected $fillable = [
        'payment_id',
        'edited_by',
        'edited_at',
        'old_values',
        'new_values',
        'reason',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'edited_at' => 'datetime',
    ];

    public $timestamps = true;

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by');
    }

    public function scopeForPayment($query, int $paymentId)
    {
        return $query->where('payment_id', $paymentId);
    }

    /**
     * Payment edits are immutable - they can never be updated or deleted
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new \Exception('Payment edits are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new \Exception('Payment edits are immutable and cannot be deleted.');
        });
    }
}
