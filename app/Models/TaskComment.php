<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TaskComment extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'message',
        'attachment',
        'attachment_path',
        'attachment_type',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAttachmentAttribute($value): ?string
    {
        return $value ?? ($this->attributes['attachment_path'] ?? null);
    }

    public function setAttachmentAttribute($value): void
    {
        $this->attributes['attachment'] = $value;
    }

    public function getAttachmentPathAttribute($value): ?string
    {
        return $value ?? ($this->attributes['attachment'] ?? null);
    }

    public function setAttachmentPathAttribute($value): void
    {
        $this->attributes['attachment_path'] = $value;
    }

    public function attachmentUrl(): ?string
    {
        $path = $this->attachment_path ?? $this->attachment;

        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
