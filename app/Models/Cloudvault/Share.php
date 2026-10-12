<?php

declare(strict_types=1);

namespace App\Models\Cloudvault;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Share extends Model
{
    protected $table = 'cloudvault_shares';

    protected $fillable = ['sheet_id', 'email', 'role'];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(Sheet::class, 'sheet_id');
    }
}
