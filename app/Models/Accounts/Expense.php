<?php

declare(strict_types=1);

namespace App\Models\Accounts;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory()
    {
        return \Database\Factories\ExpenseFactory::new();
    }

    protected $fillable = [
        'category',
        'custom_category',
        'title',
        'amount',
        'expense_date',
        'paid_via',
        'payment_account_id',
        'paid_to',
        'reference',
        'note',
        'attachment',
        'payable_amount',
        'is_paid',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payable_amount' => 'decimal:2',
        'expense_date' => 'date',
        'is_paid' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $expense) {
            if (empty($expense->payable_amount)) {
                $expense->payable_amount = $expense->amount;
            }
        });
    }

    public function scopeByCategory($query, ExpenseCategory $category)
    {
        return $query->where('category', $category->value);
    }

    public function scopeByMethod($query, PaymentMethod $method)
    {
        return $query->where('paid_via', $method->value);
    }

    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    public function paymentAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    public function getCategoryLabelAttribute(): string
    {
        if ($this->category === ExpenseCategory::CUSTOM->value && $this->custom_category) {
            return $this->custom_category;
        }

        return ExpenseCategory::from($this->category)->label();
    }

    public function getMethodLabelAttribute(): string
    {
        return PaymentMethod::from($this->paid_via)->label();
    }

    public function getFormattedAmountAttribute(): string
    {
        return '৳' . number_format((float) $this->amount, 2);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->attachment);
    }
}
