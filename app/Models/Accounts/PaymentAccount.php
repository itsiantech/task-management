<?php

declare(strict_types=1);

namespace App\Models\Accounts;

use App\Enums\PaymentAccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentAccount extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\PaymentAccountFactory::new();
    }

    protected $fillable = [
        'name',
        'type',
        'account_number',
        'balance',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function receivedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'receiving_account', 'name');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, PaymentAccountType $type)
    {
        return $query->where('type', $type->value);
    }

    public function getTypeLabelAttribute(): string
    {
        return PaymentAccountType::from($this->type)->label();
    }

    public function getFormattedBalanceAttribute(): string
    {
        return '৳' . number_format((float) $this->balance, 2);
    }

    public function recalculateBalance(): void
    {
        $totalReceived = $this->receivedPayments()->sum('amount');
        $totalSpent = 0; // Will be calculated from expenses

        $this->balance = $totalReceived - $totalSpent;
        $this->saveQuietly();
    }
}
