<?php

declare(strict_types=1);

namespace App\Models\Cloudvault;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Row extends Model
{
    protected $table = 'cloudvault_rows';

    protected $fillable = ['sheet_id', 'cells', 'status', 'position'];

    // Credentials are encrypted at rest, transparently decrypted on access.
    protected $casts = ['cells' => 'encrypted:array'];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(Sheet::class, 'sheet_id');
    }
}
