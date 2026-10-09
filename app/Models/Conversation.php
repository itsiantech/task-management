<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'platform',
        'sender_id',
        'sender_name',
        'assigned_to',
        'status',
        'priority',
        'follow_up',
        'unread',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'follow_up' => 'boolean',
        'unread' => 'boolean',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(ChannelMessage::class)->orderBy('created_at');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
