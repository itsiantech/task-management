<?php

declare(strict_types=1);

namespace App\Models\Cloudvault;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sheet extends Model
{
    protected $table = 'cloudvault_sheets';

    protected $fillable = ['user_id', 'title', 'color', 'public_link'];

    protected $casts = ['public_link' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function columns(): HasMany
    {
        return $this->hasMany(Column::class, 'sheet_id')->orderBy('position');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(Row::class, 'sheet_id')->orderBy('position');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(Share::class, 'sheet_id');
    }

    public function shareRoleFor(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        return $this->shares()->where('email', $user->email)->value('role');
    }
}
