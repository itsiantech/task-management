<?php

declare(strict_types=1);

namespace App\Models\Accounts;

use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\ServiceFactory::new();
    }

    protected $fillable = [
        'name',
        'type',
        'default_price',
        'description',
        'is_active',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $service) {
            if (empty($service->default_price)) {
                $service->default_price = 0;
            }
        });
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(\App\Models\InvoiceItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, ServiceType $type)
    {
        return $query->where('type', $type->value);
    }

    public function getTypeLabelAttribute(): string
    {
        return ServiceType::from($this->type)->label();
    }

    public function getFormattedPriceAttribute(): string
    {
        return '৳' . number_format((float) $this->default_price, 2);
    }
}
