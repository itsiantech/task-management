<?php

declare(strict_types=1);

namespace App\Models\Cloudvault;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Column extends Model
{
    protected $table = 'cloudvault_columns';

    protected $fillable = [
        'sheet_id', 'title', 'type', 'width', 'header_bg', 'header_color',
        'header_size', 'header_align', 'header_bold', 'options', 'sticky', 'position',
    ];

    protected $casts = [
        'options' => 'array',
        'header_bold' => 'boolean',
        'sticky' => 'boolean',
    ];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(Sheet::class, 'sheet_id');
    }
}
