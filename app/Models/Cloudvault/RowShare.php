<?php

declare(strict_types=1);

namespace App\Models\Cloudvault;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RowShare extends Model
{
    protected $table = 'cloudvault_row_shares';

    protected $fillable = ['sheet_id', 'row_id', 'token', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(Sheet::class, 'sheet_id');
    }

    public function row(): BelongsTo
    {
        return $this->belongsTo(Row::class, 'row_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
