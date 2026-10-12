<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_id',
        'service_id',
        'description',
        'qty',
        'unit_price',
        'discount',
        'tax',
        'line_total',
        // Legacy column, kept in sync with line_total for old screens.
        'amount',
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            // Calculate line total if not set. Legacy rows carry only
            // `amount`, so keep both columns in sync.
            if (empty($item->line_total)) {
                if (! empty($item->amount)) {
                    $item->line_total = $item->amount;
                } else {
                    $subtotal = ($item->unit_price * $item->qty) - $item->discount;
                    $item->line_total = $subtotal + $item->tax;
                }
            }
        });

        static::saved(function (self $item) {
            // Recalculate invoice totals when item changes
            $item->invoice?->recalculateTotals();
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Accounts\Service::class);
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return '৳' . number_format((float) $this->unit_price, 2);
    }

    public function getFormattedLineTotalAttribute(): string
    {
        return '৳' . number_format((float) $this->line_total, 2);
    }
}
